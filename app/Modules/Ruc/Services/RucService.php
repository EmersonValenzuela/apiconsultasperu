<?php

namespace App\Modules\Ruc\Services;

use App\Models\ConsultaLog;
use App\Models\FuenteEstado;
use App\Modules\Ruc\Clients\PadronSunatRucClient;
use App\Modules\Ruc\Clients\PeruConsultRucClient;
use App\Modules\Ruc\Contracts\RucProvider;
use App\Modules\Ruc\Support\RucValidator;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

class RucService
{
    public const CACHE_TTL_SECONDS = 604800; // 7 días (docs/02)

    /**
     * @var array<string, RucProvider>
     */
    private array $driverInstances = [];

    public function __construct()
    {
        // Registrar drivers disponibles
        $this->driverInstances[PeruConsultRucClient::DRIVER_NAME] = new PeruConsultRucClient(
            baseUrl: config('services.ruc.peru_consult.base_url'),
            token: config('services.ruc.peru_consult.token'),
            timeout: (int) config('services.ruc.peru_consult.timeout', 5),
            retryTimes: (int) config('services.ruc.peru_consult.retry_times', 2),
            retrySleep: (int) config('services.ruc.peru_consult.retry_sleep', 200),
        );

        $this->driverInstances[PadronSunatRucClient::DRIVER_NAME] = new PadronSunatRucClient();
    }

    /**
     * Permite inyectar o sustituir drivers (especialmente para pruebas con mocks).
     */
    public function setDriver(string $name, RucProvider $driver): void
    {
        $this->driverInstances[$name] = $driver;
    }

    /**
     * Consulta un RUC con validación previa de Módulo 11, caché de 7 días, auditoría y failover.
     *
     * @return array{
     *     data: array{
     *         ruc: string,
     *         razon_social: string,
     *         estado: string,
     *         condicion: string,
     *         direccion: string|null,
     *         departamento: string|null,
     *         provincia: string|null,
     *         distrito: string|null
     *     },
     *     meta: array{actualizado_en: string, fuente: string, stale: bool}
     * }|null
     *
     * @throws ValidationException Si el RUC no cumple el algoritmo del dígito verificador.
     * @throws SourceUnavailableException Si todas las fuentes fallan y no hay dato en caché.
     */
    public function consultar(string $ruc, ?string $clientIp = null): ?array
    {
        $ruc = trim($ruc);

        // 1. Validación matemática del dígito verificador (antes de cualquier llamada de red)
        if (! RucValidator::validate($ruc)) {
            $this->logConsulta($ruc, $clientIp, ConsultaLog::RESULTADO_ERROR);

            throw ValidationException::withMessages([
                'ruc' => ['El campo ruc debe tener 11 dígitos y un dígito verificador válido.'],
            ]);
        }

        // 2. Caché de 7 días
        $cacheKey = "ruc:{$ruc}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $this->logConsulta($ruc, $clientIp, ConsultaLog::RESULTADO_OK);

            return $cached;
        }

        // 3. Ejecución por cadena de drivers configurados (failover)
        $configuredDrivers = array_filter(array_map('trim', explode(',', (string) config('services.ruc.drivers', 'peru_consult,padron_sunat'))));
        $lastException = null;

        foreach ($configuredDrivers as $driverName) {
            $driver = $this->driverInstances[$driverName] ?? null;
            if ($driver === null) {
                continue;
            }

            try {
                $result = $driver->find($ruc);

                if ($result === null) {
                    // RUC no existe en SUNAT (404)
                    $this->logConsulta($ruc, $clientIp, ConsultaLog::RESULTADO_NOT_FOUND);

                    return null;
                }

                $payload = [
                    'data' => [
                        'ruc' => $result['ruc'],
                        'razon_social' => $result['razon_social'],
                        'estado' => $result['estado'],
                        'condicion' => $result['condicion'],
                        'direccion' => $result['direccion'],
                        'departamento' => $result['departamento'],
                        'provincia' => $result['provincia'],
                        'distrito' => $result['distrito'],
                    ],
                    'meta' => [
                        'actualizado_en' => now('America/Lima')->toAtomString(),
                        'fuente' => $result['fuente'],
                        'stale' => false,
                    ],
                ];

                // Guardar en caché 7 días
                Cache::put($cacheKey, $payload, self::CACHE_TTL_SECONDS);

                // Auditoría y salud
                $this->logConsulta($ruc, $clientIp, ConsultaLog::RESULTADO_OK);
                $this->recordSourceHealthSuccess();

                return $payload;
            } catch (Throwable $e) {
                $lastException = $e;
                continue; // Probar siguiente driver
            }
        }

        // Si fallaron todos los drivers
        $this->logConsulta($ruc, $clientIp, ConsultaLog::RESULTADO_ERROR);
        $this->recordSourceHealthError($lastException?->getMessage() ?? 'Todas las fuentes de RUC fallaron.');

        throw new SourceUnavailableException(
            'El servicio de consulta RUC no está disponible en este momento.',
            previous: $lastException
        );
    }

    /**
     * Purga los registros de auditoría mayores a 90 días (docs/04-base-de-datos.md).
     */
    public function purgeOldLogs(int $days = 90): int
    {
        $threshold = now('America/Lima')->subDays($days);

        return ConsultaLog::query()
            ->where('creado_en', '<', $threshold)
            ->delete();
    }

    /**
     * Registra la consulta usando HMAC-SHA256 para preservar la privacidad (docs/07).
     */
    private function logConsulta(string $documento, ?string $ip, string $resultado): void
    {
        try {
            $key = (string) config('app.key', 'ConsultaHoySecretKey');
            $docHash = hash_hmac('sha256', $documento, $key);
            $ipHash = hash_hmac('sha256', $ip ?? '127.0.0.1', $key);

            ConsultaLog::query()->create([
                'tipo' => ConsultaLog::TIPO_RUC,
                'documento_hash' => $docHash,
                'ip_hash' => $ipHash,
                'resultado' => $resultado,
                'creado_en' => now('America/Lima'),
            ]);
        } catch (Throwable) {
            // No interrumpir la respuesta si falla el log de auditoría
        }
    }

    private function recordSourceHealthSuccess(): void
    {
        FuenteEstado::query()->updateOrCreate(
            ['modulo' => 'ruc'],
            [
                'ultima_ok' => now('America/Lima'),
                'intentos_fallidos' => 0,
                'mensaje_error' => null,
            ]
        );
    }

    private function recordSourceHealthError(string $errorMessage): void
    {
        $estado = FuenteEstado::query()->firstOrNew(['modulo' => 'ruc']);
        $estado->ultimo_error = now('America/Lima');
        $estado->mensaje_error = $errorMessage;
        $estado->intentos_fallidos = ($estado->intentos_fallidos ?? 0) + 1;
        $estado->save();
    }
}
