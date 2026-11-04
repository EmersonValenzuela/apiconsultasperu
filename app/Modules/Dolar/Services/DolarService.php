<?php

namespace App\Modules\Dolar\Services;

use App\Models\FuenteEstado;
use App\Modules\Dolar\Contracts\ExchangeRateProvider;
use App\Modules\Dolar\Models\CotizacionMoneda;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class DolarService
{
    public const SUPPORTED_CURRENCIES = ['PEN', 'USD', 'EUR', 'GBP', 'BRL', 'CLP', 'COP', 'MXN', 'ARS'];

    public const CACHE_KEY_CURRENT = 'dolar:actual';

    public const CACHE_KEY_RATES = 'monedas:tasas:USD';

    public const CACHE_TTL_SECONDS = 2100; // 35 minutos

    public function __construct(
        private readonly ExchangeRateProvider $provider,
    ) {
    }

    /**
     * Sincroniza las cotizaciones desde la fuente externa, actualiza la base de datos,
     * la caché y registra el resultado en fuentes_estado.
     *
     * @return array{
     *     data: array{moneda: string, compra: float, venta: float, fecha: string},
     *     meta: array{actualizado_en: string, fuente: string, stale: bool, tipo: string}
     * }
     */
    public function syncRates(): array
    {
        try {
            $fetched = $this->provider->getRates('USD');

            $penRate = (float) ($fetched['tasas']['PEN'] ?? null);
            if ($penRate <= 0) {
                throw new SourceUnavailableException('La tasa para PEN no vino en la respuesta de la fuente.');
            }

            $date = $fetched['fecha'];
            $updatedAt = $fetched['actualizado_en'];
            $source = $fetched['fuente'];
            $type = $fetched['tipo'];

            // Upsert en cotizaciones_moneda
            $cotizacion = CotizacionMoneda::query()->updateOrCreate(
                [
                    'moneda' => 'USD',
                    'fecha' => $date,
                    'fuente' => $source,
                ],
                [
                    'compra' => $penRate,
                    'venta' => $penRate,
                    'tipo' => $type,
                    'obtenido_en' => $updatedAt,
                ]
            );

            $payload = [
                'data' => [
                    'moneda' => 'USD',
                    'compra' => (float) $cotizacion->compra,
                    'venta' => (float) $cotizacion->venta,
                    'fecha' => $cotizacion->fecha->format('Y-m-d'),
                ],
                'meta' => [
                    'actualizado_en' => $updatedAt->toAtomString(),
                    'fuente' => $source,
                    'stale' => false,
                    'tipo' => $type,
                ],
            ];

            // Guardar en caché
            Cache::put(self::CACHE_KEY_CURRENT, $payload, self::CACHE_TTL_SECONDS);
            Cache::put(self::CACHE_KEY_RATES, [
                'tasas' => $fetched['tasas'],
                'actualizado_en' => $updatedAt->toAtomString(),
                'fuente' => $source,
                'stale' => false,
            ], self::CACHE_TTL_SECONDS);

            // Registrar éxito en fuentes_estado
            $this->recordSourceHealthSuccess();

            return $payload;
        } catch (Throwable $e) {
            $this->recordSourceHealthError($e->getMessage());

            throw $e;
        }
    }

    /**
     * Obtiene la cotización actual del dólar con comportamiento stale-while-error.
     *
     * @return array{
     *     data: array{moneda: string, compra: float, venta: float, fecha: string},
     *     meta: array{actualizado_en: string, fuente: string, stale: bool, tipo: string}
     * }
     */
    public function getDolarActual(): array
    {
        $cached = Cache::get(self::CACHE_KEY_CURRENT);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            return $this->syncRates();
        } catch (Throwable $e) {
            // Stale fallback: buscar último dato guardado en base de datos
            $latest = CotizacionMoneda::query()
                ->where('moneda', 'USD')
                ->orderByDesc('fecha')
                ->first();

            if ($latest !== null) {
                return [
                    'data' => [
                        'moneda' => 'USD',
                        'compra' => (float) $latest->compra,
                        'venta' => (float) $latest->venta,
                        'fecha' => $latest->fecha->format('Y-m-d'),
                    ],
                    'meta' => [
                        'actualizado_en' => $latest->obtenido_en->toAtomString(),
                        'fuente' => $latest->fuente,
                        'stale' => true,
                        'tipo' => $latest->tipo,
                    ],
                ];
            }

            if ($e instanceof SourceUnavailableException) {
                throw $e;
            }

            throw new SourceUnavailableException(
                'No hay cotización del dólar disponible en este momento.',
                previous: $e
            );
        }
    }

    /**
     * Consulta el histórico de cotizaciones para un rango de fechas.
     *
     * @return array{
     *     data: array<int, array{fecha: string, compra: float, venta: float}>,
     *     meta: array{total: int, actualizado_en: string|null, fuente: string, stale: bool}
     * }
     */
    public function getHistorico(string $desde, string $hasta, string $moneda = 'USD'): array
    {
        $records = CotizacionMoneda::query()
            ->where('moneda', strtoupper($moneda))
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'asc')
            ->get();

        $data = $records->map(fn (CotizacionMoneda $c) => [
            'fecha' => $c->fecha->format('Y-m-d'),
            'compra' => (float) $c->compra,
            'venta' => (float) $c->venta,
        ])->all();

        $latestObtained = $records->max('obtenido_en');
        $fuente = $records->first()?->fuente ?? 'open.er-api.com';

        return [
            'data' => $data,
            'meta' => [
                'total' => count($data),
                'actualizado_en' => $latestObtained instanceof Carbon
                    ? $latestObtained->toAtomString()
                    : ($latestObtained ? Carbon::parse($latestObtained)->toAtomString() : now('America/Lima')->toAtomString()),
                'fuente' => $fuente,
                'stale' => false,
            ],
        ];
    }

    /**
     * Convierte un monto entre dos divisas soportadas.
     *
     * @return array{
     *     data: array{de: string, a: string, monto: float, resultado: float, tasa: float},
     *     meta: array{actualizado_en: string, fuente: string, stale: bool}
     * }
     */
    public function convertir(string $de, string $a, float $monto): array
    {
        $de = strtoupper(trim($de));
        $a = strtoupper(trim($a));

        $ratesData = Cache::get(self::CACHE_KEY_RATES);

        if (! is_array($ratesData) || empty($ratesData['tasas'])) {
            try {
                $this->syncRates();
                $ratesData = Cache::get(self::CACHE_KEY_RATES);
            } catch (Throwable) {
                // Si falla la sincronización, buscar cotización de USD/PEN en base de datos como fallback
                $latest = CotizacionMoneda::query()->where('moneda', 'USD')->orderByDesc('fecha')->first();
                if ($latest !== null) {
                    $ratesData = [
                        'tasas' => [
                            'USD' => 1.0,
                            'PEN' => (float) $latest->venta,
                        ],
                        'actualizado_en' => $latest->obtenido_en->toAtomString(),
                        'fuente' => $latest->fuente,
                        'stale' => true,
                    ];
                }
            }
        }

        if (! is_array($ratesData) || empty($ratesData['tasas'])) {
            throw new SourceUnavailableException('No hay tasas disponibles para realizar la conversión.');
        }

        $rates = $ratesData['tasas'];
        if (! isset($rates[$de]) || ! isset($rates[$a])) {
            throw new SourceUnavailableException("Tasa no disponible para el par {$de} / {$a}.");
        }

        $rateDe = (float) $rates[$de];
        $rateA = (float) $rates[$a];

        if ($rateDe <= 0) {
            throw new SourceUnavailableException("Tasa base inválida para {$de}.");
        }

        // Tasa de cambio de 'de' a 'a': (1 USD = rateA 'a') / (1 USD = rateDe 'de') => rateA / rateDe
        $tasa = $rateA / $rateDe;
        $resultado = round($monto * $tasa, 2);

        return [
            'data' => [
                'de' => $de,
                'a' => $a,
                'monto' => $monto,
                'resultado' => $resultado,
                'tasa' => round($tasa, 4),
            ],
            'meta' => [
                'actualizado_en' => $ratesData['actualizado_en'] ?? now('America/Lima')->toAtomString(),
                'fuente' => $ratesData['fuente'] ?? 'open.er-api.com',
                'stale' => (bool) ($ratesData['stale'] ?? false),
            ],
        ];
    }

    private function recordSourceHealthSuccess(): void
    {
        FuenteEstado::query()->updateOrCreate(
            ['modulo' => 'dolar'],
            [
                'ultima_ok' => now('America/Lima'),
                'intentos_fallidos' => 0,
                'mensaje_error' => null,
            ]
        );
    }

    private function recordSourceHealthError(string $errorMessage): void
    {
        FuenteEstado::query()->updateOrCreate(
            ['modulo' => 'dolar'],
            [
                'ultimo_error' => now('America/Lima'),
                'mensaje_error' => $errorMessage,
                'intentos_fallidos' => DB::raw('intentos_fallidos + 1'),
            ]
        );
    }
}
