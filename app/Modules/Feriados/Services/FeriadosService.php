<?php

namespace App\Modules\Feriados\Services;

use App\Models\FuenteEstado;
use App\Modules\Feriados\Contracts\HolidayProvider;
use App\Modules\Feriados\Models\Feriado;
use Illuminate\Support\Carbon;
use Throwable;

class FeriadosService
{
    public function __construct(
        private readonly HolidayProvider $provider,
    ) {
    }

    /**
     * Sincroniza los feriados de un año desde la fuente externa.
     * NUNCA sobreescribe ni borra registros con fuente = manual.
     */
    public function syncHolidays(?int $year = null): int
    {
        $year = $year ?? (int) now('America/Lima')->format('Y');

        try {
            $items = $this->provider->getHolidays($year);

            $processed = 0;
            foreach ($items as $item) {
                $existing = Feriado::query()
                    ->where('fecha', $item['fecha'])
                    ->where('nombre', $item['nombre'])
                    ->first();

                // Regla obligatoria: nunca sobreescribir ni tocar feriados manuales
                if ($existing !== null && $existing->fuente === Feriado::FUENTE_MANUAL) {
                    continue;
                }

                Feriado::query()->updateOrCreate(
                    [
                        'fecha' => $item['fecha'],
                        'nombre' => $item['nombre'],
                    ],
                    [
                        'tipo' => $item['tipo'] ?? Feriado::TIPO_FERIADO,
                        'anio' => $year,
                        'fuente' => Feriado::FUENTE_NAGER,
                    ]
                );

                $processed++;
            }

            $this->recordSourceHealthSuccess();

            return $processed;
        } catch (Throwable $e) {
            $this->recordSourceHealthError($e->getMessage());

            throw $e;
        }
    }

    /**
     * Devuelve la lista de feriados para un año dado.
     *
     * @return array{
     *     data: array<int, array{fecha: string, nombre: string, tipo: string}>,
     *     meta: array{total: int, anio: int, actualizado_en: string, fuente: string, stale: bool}
     * }
     */
    public function getFeriados(int $anio): array
    {
        $feriados = Feriado::query()
            ->where('anio', $anio)
            ->orderBy('fecha', 'asc')
            ->get();

        $data = $feriados->map(fn (Feriado $f) => [
            'fecha' => $f->fecha->format('Y-m-d'),
            'nombre' => $f->nombre,
            'tipo' => $f->tipo,
        ])->all();

        $estado = FuenteEstado::query()->find('feriados');
        $stale = $estado?->hasActiveError() ?? false;
        $actualizadoEn = $estado?->ultima_ok?->toAtomString() ?? now('America/Lima')->toAtomString();

        return [
            'data' => $data,
            'meta' => [
                'total' => count($data),
                'anio' => $anio,
                'actualizado_en' => $actualizadoEn,
                'fuente' => 'nager/manual',
                'stale' => $stale,
            ],
        ];
    }

    /**
     * Obtiene el próximo feriado a partir de hoy (zona America/Lima) y calcula los días restantes.
     *
     * @return array{
     *     data: array{fecha: string, nombre: string, tipo: string, dias_faltantes: int}|null,
     *     meta: array{actualizado_en: string, fuente: string, stale: bool}
     * }
     */
    public function getProximoFeriado(): array
    {
        $today = now('America/Lima')->startOfDay();
        $todayStr = $today->format('Y-m-d');

        /** @var Feriado|null $proximo */
        $proximo = Feriado::query()
            ->where('fecha', '>=', $todayStr)
            ->orderBy('fecha', 'asc')
            ->first();

        // Si no hay más en el año actual, buscar en el siguiente año
        if ($proximo === null) {
            $nextYear = $today->year + 1;
            $proximo = Feriado::query()
                ->where('anio', '>=', $nextYear)
                ->orderBy('fecha', 'asc')
                ->first();
        }

        $estado = FuenteEstado::query()->find('feriados');
        $stale = $estado?->hasActiveError() ?? false;
        $actualizadoEn = $estado?->ultima_ok?->toAtomString() ?? now('America/Lima')->toAtomString();

        if ($proximo === null) {
            return [
                'data' => null,
                'meta' => [
                    'actualizado_en' => $actualizadoEn,
                    'fuente' => 'nager/manual',
                    'stale' => $stale,
                ],
            ];
        }

        $fechaFeriado = Carbon::createFromFormat('Y-m-d', $proximo->fecha->format('Y-m-d'), 'America/Lima')->startOfDay();
        $diasFaltantes = (int) $today->diffInDays($fechaFeriado, false);

        return [
            'data' => [
                'fecha' => $proximo->fecha->format('Y-m-d'),
                'nombre' => $proximo->nombre,
                'tipo' => $proximo->tipo,
                'dias_faltantes' => max(0, $diasFaltantes),
            ],
            'meta' => [
                'actualizado_en' => $actualizadoEn,
                'fuente' => $proximo->fuente,
                'stale' => $stale,
            ],
        ];
    }

    /**
     * Agrega o actualiza manualmente un día no laborable o feriado puente.
     */
    public function addFeriado(string $fecha, string $nombre, string $tipo = Feriado::TIPO_FERIADO): Feriado
    {
        $anio = (int) Carbon::parse($fecha)->format('Y');

        return Feriado::query()->updateOrCreate(
            [
                'fecha' => $fecha,
                'nombre' => $nombre,
            ],
            [
                'tipo' => $tipo,
                'anio' => $anio,
                'fuente' => Feriado::FUENTE_MANUAL,
            ]
        );
    }

    private function recordSourceHealthSuccess(): void
    {
        FuenteEstado::query()->updateOrCreate(
            ['modulo' => 'feriados'],
            [
                'ultima_ok' => now('America/Lima'),
                'intentos_fallidos' => 0,
                'mensaje_error' => null,
            ]
        );
    }

    private function recordSourceHealthError(string $errorMessage): void
    {
        $estado = FuenteEstado::query()->firstOrNew(['modulo' => 'feriados']);
        $estado->ultimo_error = now('America/Lima');
        $estado->mensaje_error = $errorMessage;
        $estado->intentos_fallidos = ($estado->intentos_fallidos ?? 0) + 1;
        $estado->save();
    }
}
