<?php

namespace App\Modules\Dni\Clients;

use App\Modules\Dni\Contracts\DniProvider;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Facades\Http;
use Throwable;

class PeruConsultDniClient implements DniProvider
{
    public const DRIVER_NAME = 'peru_consult';

    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?string $token = null,
        private readonly int $timeout = 5,
        private readonly int $retryTimes = 2,
        private readonly int $retrySleep = 200,
    ) {
    }

    public function name(): string
    {
        return self::DRIVER_NAME;
    }

    public function find(string $dni): ?array
    {
        $baseUrl = rtrim($this->baseUrl ?? config('services.dni.peru_consult.base_url', 'http://localhost:8080'), '/');
        $token = $this->token ?? config('services.dni.peru_consult.token');
        $url = "{$baseUrl}/api/v1/dni/{$dni}";

        try {
            $request = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep, throw: false)
                ->withHeaders([
                    'User-Agent' => 'ConsultaHoyPeru/1.0 (+https://consultahoy.pe)',
                    'Accept' => 'application/json',
                ]);

            if (! empty($token)) {
                $request = $request->withToken($token);
            }

            $response = $request->get($url);

            if ($response->status() === 404) {
                return null;
            }

            if (! $response->successful()) {
                throw new SourceUnavailableException("Error de peru-consult-api: HTTP {$response->status()}");
            }

            $data = $response->json();

            if (isset($data['data']) && is_array($data['data'])) {
                $data = $data['data'];
            }

            if (! is_array($data) || empty($data['dni']) && empty($data['nombres'])) {
                return null;
            }

            return [
                'dni' => (string) ($data['dni'] ?? $dni),
                'nombres' => trim((string) ($data['nombres'] ?? $data['nombre'] ?? '')),
                'apellido_paterno' => trim((string) ($data['apellido_paterno'] ?? $data['apellidoPaterno'] ?? $data['paterno'] ?? '')),
                'apellido_materno' => trim((string) ($data['apellido_materno'] ?? $data['apellidoMaterno'] ?? $data['materno'] ?? '')),
                'fuente' => self::DRIVER_NAME,
            ];
        } catch (SourceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SourceUnavailableException(
                'No se pudo conectar con el servicio de consulta DNI: '.$e->getMessage(),
                previous: $e
            );
        }
    }
}
