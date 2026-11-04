<?php

namespace App\Modules\Ruc\Clients;

use App\Modules\Ruc\Contracts\RucProvider;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Facades\Http;
use Throwable;

class PeruConsultRucClient implements RucProvider
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

    public function find(string $ruc): ?array
    {
        $baseUrl = rtrim($this->baseUrl ?? config('services.ruc.peru_consult.base_url', 'http://peru-consult-api:8080'), '/');
        $token = $this->token ?? config('services.ruc.peru_consult.token');
        $url = "{$baseUrl}/api/v1/ruc/{$ruc}";

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

            if (! is_array($data) || empty($data['ruc'])) {
                return null;
            }

            return [
                'ruc' => (string) $data['ruc'],
                'razon_social' => (string) ($data['razon_social'] ?? $data['razonSocial'] ?? $data['nombre'] ?? ''),
                'estado' => strtoupper((string) ($data['estado'] ?? 'ACTIVO')),
                'condicion' => strtoupper((string) ($data['condicion'] ?? 'HABIDO')),
                'direccion' => isset($data['direccion']) ? (string) $data['direccion'] : null,
                'departamento' => isset($data['departamento']) ? (string) $data['departamento'] : null,
                'provincia' => isset($data['provincia']) ? (string) $data['provincia'] : null,
                'distrito' => isset($data['distrito']) ? (string) $data['distrito'] : null,
                'fuente' => self::DRIVER_NAME,
            ];
        } catch (SourceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SourceUnavailableException(
                'No se pudo conectar con el servicio peru-consult-api: '.$e->getMessage(),
                previous: $e
            );
        }
    }
}
