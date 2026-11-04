<?php

namespace App\Modules\Feriados\Clients;

use App\Modules\Feriados\Contracts\HolidayProvider;
use App\Modules\Feriados\Models\Feriado;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Facades\Http;
use Throwable;

class NagerDateClient implements HolidayProvider
{
    public const SOURCE_NAME = 'nager';

    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly int $timeout = 5,
        private readonly int $retryTimes = 2,
        private readonly int $retrySleep = 200,
    ) {
    }

    public function getHolidays(int $year): array
    {
        $baseUrl = rtrim($this->baseUrl ?? config('services.feriados.nager.base_url', 'https://date.nager.at/api/v3'), '/');
        $url = "{$baseUrl}/PublicHolidays/{$year}/PE";

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep)
                ->withHeaders([
                    'User-Agent' => 'ConsultaHoyPeru/1.0 (+https://consultahoy.pe)',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new SourceUnavailableException("Error al consultar Nager.Date: HTTP {$response->status()}");
            }

            $json = $response->json();
            if (! is_array($json)) {
                throw new SourceUnavailableException('Estructura inesperada recibida de Nager.Date.');
            }

            $feriados = [];
            foreach ($json as $item) {
                if (! is_array($item) || empty($item['date']) || (empty($item['localName']) && empty($item['name']))) {
                    continue;
                }

                $nombre = (string) ($item['localName'] ?: $item['name']);

                $feriados[] = [
                    'fecha' => (string) $item['date'],
                    'nombre' => $nombre,
                    'tipo' => Feriado::TIPO_FERIADO,
                    'anio' => $year,
                    'fuente' => self::SOURCE_NAME,
                ];
            }

            return $feriados;
        } catch (SourceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SourceUnavailableException(
                'No se pudo conectar con el servicio de feriados Nager.Date: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
