<?php

namespace App\Modules\Sismos\Clients;

use App\Modules\Sismos\Contracts\EarthquakeProvider;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class UsgsEarthquakeClient implements EarthquakeProvider
{
    public const SOURCE_NAME = 'USGS';

    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly int $timeout = 5,
        private readonly int $retryTimes = 2,
        private readonly int $retrySleep = 200,
        private readonly float $minLatitude = -19.0,
        private readonly float $maxLatitude = 0.5,
        private readonly float $minLongitude = -82.0,
        private readonly float $maxLongitude = -68.0,
    ) {
    }

    public function getRecentEarthquakes(int $days = 7, float $minMagnitude = 0, int $limit = 100): array
    {
        $url = $this->baseUrl ?? config('services.sismos.usgs.base_url', 'https://earthquake.usgs.gov/fdsnws/event/1/query');
        $startTime = now('America/Lima')->subDays($days)->format('Y-m-d');

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep)
                ->withHeaders([
                    'User-Agent' => 'ConsultaHoyPeru/1.0 (+https://consultahoy.pe)',
                    'Accept' => 'application/json',
                ])
                ->get($url, [
                    'format' => 'geojson',
                    'starttime' => $startTime,
                    'minmagnitude' => $minMagnitude,
                    'minlatitude' => $this->minLatitude,
                    'maxlatitude' => $this->maxLatitude,
                    'minlongitude' => $this->minLongitude,
                    'maxlongitude' => $this->maxLongitude,
                    'orderby' => 'time',
                    'limit' => $limit,
                ]);

            if (! $response->successful()) {
                throw new SourceUnavailableException("Error al consultar USGS: HTTP {$response->status()}");
            }

            $json = $response->json();
            if (! is_array($json) || ! isset($json['features']) || ! is_array($json['features'])) {
                throw new SourceUnavailableException('Estructura GeoJSON inválida recibida de USGS.');
            }

            $sismos = [];

            foreach ($json['features'] as $feature) {
                if (! $this->isValidFeature($feature)) {
                    continue;
                }

                $place = (string) ($feature['properties']['place'] ?? '');

                // Criterio de filtrado geográfico para Perú:
                // El recuadro geográfico (bounding box) [-19, 0.5, -82, -68] cubre zonas fronterizas
                // de países vecinos (Chile, Ecuador, Bolivia, Brasil, Colombia).
                // Descartamos los eventos cuya descripción de lugar especifique un país limítrofe.
                if ($this->isNeighborCountry($place)) {
                    continue;
                }

                $coordinates = $feature['geometry']['coordinates'] ?? [];
                $timestampMs = (int) ($feature['properties']['time'] ?? 0);

                $sismos[] = [
                    'id_externo' => (string) $feature['id'],
                    'magnitud' => (float) ($feature['properties']['mag'] ?? 0),
                    'lugar' => $place,
                    'profundidad_km' => isset($coordinates[2]) ? (float) $coordinates[2] : null,
                    'latitud' => (float) ($coordinates[1] ?? 0),
                    'longitud' => (float) ($coordinates[0] ?? 0),
                    'ocurrido_en' => Carbon::createFromTimestampMs($timestampMs, 'UTC')->setTimezone('America/Lima'),
                    'fuente' => self::SOURCE_NAME,
                    'url' => $feature['properties']['url'] ?? null,
                    'crudo' => $feature,
                ];
            }

            return $sismos;
        } catch (SourceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SourceUnavailableException(
                'No se pudo conectar con el catálogo de sismos de USGS: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    private function isValidFeature(mixed $feature): bool
    {
        return is_array($feature)
            && ! empty($feature['id'])
            && isset($feature['properties'])
            && is_array($feature['properties'])
            && isset($feature['geometry']['coordinates'])
            && is_array($feature['geometry']['coordinates'])
            && count($feature['geometry']['coordinates']) >= 2;
    }

    /**
     * Determina si la descripción del lugar corresponde explícitamente a un país vecino.
     */
    private function isNeighborCountry(string $place): bool
    {
        $placeLower = Str::lower($place);

        $neighbors = ['chile', 'ecuador', 'bolivia', 'brazil', 'brasil', 'colombia'];

        foreach ($neighbors as $neighbor) {
            if (Str::contains($placeLower, $neighbor)) {
                return true;
            }
        }

        return false;
    }
}
