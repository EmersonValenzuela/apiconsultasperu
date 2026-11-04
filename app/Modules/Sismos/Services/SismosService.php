<?php

namespace App\Modules\Sismos\Services;

use App\Models\FuenteEstado;
use App\Modules\Sismos\Contracts\EarthquakeProvider;
use App\Modules\Sismos\Models\Sismo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class SismosService
{
    public const CACHE_TTL_SECONDS = 360; // 6 minutos

    public function __construct(
        private readonly EarthquakeProvider $provider,
    ) {
    }

    /**
     * Sincroniza sismos recientes desde el proveedor externo e inserta o actualiza en la base de datos.
     */
    public function syncSismos(int $days = 7, float $minMagnitude = 0, int $limit = 100): int
    {
        try {
            $events = $this->provider->getRecentEarthquakes($days, $minMagnitude, $limit);

            if (empty($events)) {
                $this->recordSourceHealthSuccess();

                return 0;
            }

            $now = now('America/Lima');

            $upsertData = array_map(function (array $item) use ($now) {
                return [
                    'id_externo' => $item['id_externo'],
                    'magnitud' => $item['magnitud'],
                    'lugar' => $item['lugar'],
                    'profundidad_km' => $item['profundidad_km'],
                    'latitud' => $item['latitud'],
                    'longitud' => $item['longitud'],
                    'ocurrido_en' => $item['ocurrido_en']->format('Y-m-d H:i:sP'),
                    'fuente' => $item['fuente'],
                    'url' => $item['url'],
                    'crudo' => json_encode($item['crudo'], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now->format('Y-m-d H:i:sP'),
                    'updated_at' => $now->format('Y-m-d H:i:sP'),
                ];
            }, $events);

            Sismo::query()->upsert(
                $upsertData,
                ['id_externo'],
                ['magnitud', 'lugar', 'profundidad_km', 'latitud', 'longitud', 'ocurrido_en', 'fuente', 'url', 'crudo', 'updated_at']
            );

            $this->recordSourceHealthSuccess();

            return count($upsertData);
        } catch (Throwable $e) {
            $this->recordSourceHealthError($e->getMessage());

            throw $e;
        }
    }

    /**
     * Obtiene la lista de sismos aplicando filtros y ordenamiento más reciente primero.
     *
     * @return array{
     *     data: array<int, array{
     *         id: int,
     *         magnitud: float,
     *         lugar: string,
     *         profundidad_km: float|null,
     *         latitud: float,
     *         longitud: float,
     *         ocurrido_en: string,
     *         fuente: string,
     *         url: string|null
     *     }>,
     *     meta: array{total: int, actualizado_en: string, fuente: string, stale: bool}
     * }
     */
    public function getSismos(float $minMagnitude = 0, int $days = 7, int $limit = 50): array
    {
        $since = now('America/Lima')->subDays($days);

        $query = Sismo::query()
            ->where('ocurrido_en', '>=', $since)
            ->where('magnitud', '>=', $minMagnitude)
            ->orderByDesc('ocurrido_en')
            ->limit($limit);

        $sismos = $query->get();

        $data = $sismos->map(fn (Sismo $s) => [
            'id' => $s->id,
            'magnitud' => (float) $s->magnitud,
            'lugar' => $s->lugar,
            'profundidad_km' => $s->profundidad_km !== null ? (float) $s->profundidad_km : null,
            'latitud' => (float) $s->latitud,
            'longitud' => (float) $s->longitud,
            'ocurrido_en' => $s->ocurrido_en->toAtomString(),
            'fuente' => $s->fuente,
            'url' => $s->url,
        ])->all();

        $estado = FuenteEstado::query()->find('sismos');
        $stale = $estado?->hasActiveError() ?? false;
        $actualizadoEn = $estado?->ultima_ok?->toAtomString() ?? now('America/Lima')->toAtomString();

        return [
            'data' => $data,
            'meta' => [
                'total' => count($data),
                'actualizado_en' => $actualizadoEn,
                'fuente' => 'USGS',
                'stale' => $stale,
            ],
        ];
    }

    /**
     * Obtiene el detalle de un sismo por su ID.
     *
     * @throws ModelNotFoundException
     * @return array{
     *     data: array{
     *         id: int,
     *         magnitud: float,
     *         lugar: string,
     *         profundidad_km: float|null,
     *         latitud: float,
     *         longitud: float,
     *         ocurrido_en: string,
     *         fuente: string,
     *         url: string|null
     *     },
     *     meta: array{actualizado_en: string, fuente: string, stale: bool}
     * }
     */
    public function getSismo(int $id): array
    {
        /** @var Sismo $s */
        $s = Sismo::query()->findOrFail($id);

        $estado = FuenteEstado::query()->find('sismos');
        $stale = $estado?->hasActiveError() ?? false;
        $actualizadoEn = $estado?->ultima_ok?->toAtomString() ?? $s->updated_at->toAtomString();

        return [
            'data' => [
                'id' => $s->id,
                'magnitud' => (float) $s->magnitud,
                'lugar' => $s->lugar,
                'profundidad_km' => $s->profundidad_km !== null ? (float) $s->profundidad_km : null,
                'latitud' => (float) $s->latitud,
                'longitud' => (float) $s->longitud,
                'ocurrido_en' => $s->ocurrido_en->toAtomString(),
                'fuente' => $s->fuente,
                'url' => $s->url,
            ],
            'meta' => [
                'actualizado_en' => $actualizadoEn,
                'fuente' => $s->fuente,
                'stale' => $stale,
            ],
        ];
    }

    /**
     * Purga sismos con más de 1 año de antigüedad (retención de docs/04).
     */
    public function purgeOldSismos(int $retentionDays = 365): int
    {
        $threshold = now('America/Lima')->subDays($retentionDays);

        return Sismo::query()
            ->where('ocurrido_en', '<', $threshold)
            ->delete();
    }

    private function recordSourceHealthSuccess(): void
    {
        FuenteEstado::query()->updateOrCreate(
            ['modulo' => 'sismos'],
            [
                'ultima_ok' => now('America/Lima'),
                'intentos_fallidos' => 0,
                'mensaje_error' => null,
            ]
        );
    }

    private function recordSourceHealthError(string $errorMessage): void
    {
        $estado = FuenteEstado::query()->firstOrNew(['modulo' => 'sismos']);
        $estado->ultimo_error = now('America/Lima');
        $estado->mensaje_error = $errorMessage;
        $estado->intentos_fallidos = ($estado->intentos_fallidos ?? 0) + 1;
        $estado->save();
    }
}
