<?php

namespace App\Modules\Dolar\Clients;

use App\Modules\Dolar\Contracts\ExchangeRateProvider;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenErApiClient implements ExchangeRateProvider
{
    public const SOURCE_NAME = 'open.er-api.com';

    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly int $timeout = 5,
        private readonly int $retryTimes = 2,
        private readonly int $retrySleep = 200,
    ) {
    }

    public function getRates(string $base = 'USD'): array
    {
        $base = strtoupper(trim($base));
        $url = ($this->baseUrl ?? config('services.dolar.open_er_api.base_url', 'https://open.er-api.com/v6')) . "/latest/{$base}";

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep)
                ->withHeaders([
                    'User-Agent' => 'ConsultaHoyPeru/1.0 (+https://consultahoy.pe)',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new SourceUnavailableException(
                    "Error al consultar open.er-api.com: HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            if (! is_array($data) || ($data['result'] ?? null) !== 'success' || empty($data['rates'])) {
                throw new SourceUnavailableException(
                    'Respuesta inesperada de open.er-api.com'
                );
            }

            $updatedAt = isset($data['time_last_update_unix'])
                ? Carbon::createFromTimestampUTC((int) $data['time_last_update_unix'])->setTimezone('America/Lima')
                : now('America/Lima');

            $date = $updatedAt->format('Y-m-d');

            /** @var array<string, float> $rates */
            $rates = [];
            foreach ($data['rates'] as $currency => $rate) {
                $rates[(string) $currency] = (float) $rate;
            }

            return [
                'fuente' => self::SOURCE_NAME,
                'base' => $base,
                'fecha' => $date,
                'actualizado_en' => $updatedAt,
                'tipo' => 'referencial',
                'tasas' => $rates,
            ];
        } catch (SourceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SourceUnavailableException(
                'No se pudo conectar con la fuente de cotizaciones: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
