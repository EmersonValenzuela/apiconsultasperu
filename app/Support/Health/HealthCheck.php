<?php

namespace App\Support\Health;

use App\Models\FuenteEstado;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Revisa base de datos, store de caché configurado y estado de cada módulo.
 *
 * Estados: ok | degradado (caché caída o algún módulo con error) | error (BD caída).
 */
class HealthCheck
{
    public const STATUS_OK = 'ok';

    public const STATUS_DEGRADED = 'degradado';

    public const STATUS_ERROR = 'error';

    /**
     * @return array{estado: string, base_de_datos: array<string, mixed>, cache: array<string, mixed>, modulos: array<string, array{ultima_ok: string|null, error: string|null}>}
     */
    public function run(): array
    {
        $database = $this->checkDatabase();
        $cache = $this->checkCache();
        $modules = $database['ok'] ? $this->modules() : [];

        $status = match (true) {
            ! $database['ok'] => self::STATUS_ERROR,
            ! $cache['ok'], $this->anyModuleWithError($modules) => self::STATUS_DEGRADED,
            default => self::STATUS_OK,
        };

        return [
            'estado' => $status,
            'base_de_datos' => $database,
            'cache' => $cache,
            'modulos' => $modules,
        ];
    }

    /**
     * @return array{ok: bool, conexion: string}
     */
    private function checkDatabase(): array
    {
        $connection = (string) config('database.default');

        try {
            DB::connection()->select('select 1');

            return ['ok' => true, 'conexion' => $connection];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'conexion' => $connection];
        }
    }

    /**
     * @return array{ok: bool, store: string}
     */
    private function checkCache(): array
    {
        $store = (string) config('cache.default');

        try {
            $key = 'health:'.Str::random(12);
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return ['ok' => $ok, 'store' => $store];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'store' => $store];
        }
    }

    /**
     * @return array<string, array{ultima_ok: string|null, error: string|null}>
     */
    private function modules(): array
    {
        try {
            $rows = FuenteEstado::query()->get()->keyBy('modulo');
        } catch (Throwable $e) {
            report($e);
            $rows = collect();
        }

        $modules = [];

        foreach (FuenteEstado::MODULES as $module) {
            /** @var FuenteEstado|null $row */
            $row = $rows->get($module);

            $modules[$module] = [
                'ultima_ok' => $row?->ultima_ok?->toAtomString(),
                'error' => $row?->hasActiveError() ? ($row->mensaje_error ?? 'Error desconocido') : null,
            ];
        }

        return $modules;
    }

    /**
     * @param  array<string, array{ultima_ok: string|null, error: string|null}>  $modules
     */
    private function anyModuleWithError(array $modules): bool
    {
        foreach ($modules as $module) {
            if ($module['error'] !== null) {
                return true;
            }
        }

        return false;
    }
}
