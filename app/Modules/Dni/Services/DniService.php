<?php

namespace App\Modules\Dni\Services;

use App\Models\ConsultaLog;
use App\Models\FuenteEstado;
use App\Modules\Dni\Clients\PeruConsultDniClient;
use App\Modules\Dni\Contracts\DniProvider;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class DniService
{
    public const CACHE_TTL_SECONDS = 3600; // Máximo 1 hora según docs/02 y docs/07

    private DniProvider $provider;

    public function __construct(
        private readonly TurnstileVerifier $turnstile,
        ?DniProvider $provider = null,
    ) {
        $this->provider = $provider ?? new PeruConsultDniClient(
            baseUrl: config('services.dni.peru_consult.base_url'),
            token: config('services.dni.peru_consult.token'),
            timeout: (int) config('services.dni.peru_consult.timeout', 5),
            retryTimes: (int) config('services.dni.peru_consult.retry_times', 2),
            retrySleep: (int) config('services.dni.peru_consult.retry_sleep', 200),
        );
    }

    /**
     * Permite sustituir el proveedor (mock para pruebas).
     */
    public function setProvider(DniProvider $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * Valida el formato del DNI (8 dígitos numéricos) sin consultar ninguna fuente externa (docs/03).
     *
     * @return array{dni: string, valido: bool, formato: string}
     */
    public function validarFormato(string $dni): array
    {
        $dni = trim($dni);
        $valido = (bool) preg_match('/^[0-9]{8}$/', $dni);

        return [
            'dni' => $dni,
            'valido' => $valido,
            'formato' => 'DNI estándar (8 dígitos)',
        ];
    }

    /**
     * Consulta los datos de un DNI bajo demanda con verificación de captcha Turnstile y minimización de datos.
     *
     * @return array{
     *     data: array{
     *         dni_enmascarado: string,
     *         nombres: string,
     *         apellido_paterno: string,
     *         apellido_materno: string
     *     },
     *     meta: array{actualizado_en: string, fuente: string, stale: bool}
     * }|null
     *
     * @throws NotFoundHttpException Si el módulo DNI está deshabilitado (DNI_ENABLED=false).
     * @throws ValidationException Si el DNI tiene formato inválido o el captcha es incorrecto.
     * @throws SourceUnavailableException Si la fuente no responde.
     */
    public function consultar(string $dni, string $captchaToken, ?string $clientIp = null): ?array
    {
        // 1. Interruptor de apagado por privacidad (docs/07: DNI_ENABLED=false responde 404)
        if (! config('services.dni.enabled', false)) {
            throw new NotFoundHttpException('El servicio de consulta DNI se encuentra deshabilitado.');
        }

        $dni = trim($dni);

        // 2. Validación de formato de 8 dígitos
        if (! preg_match('/^[0-9]{8}$/', $dni)) {
            throw ValidationException::withMessages([
                'dni' => ['El campo dni debe tener exactamente 8 dígitos numéricos.'],
            ]);
        }

        // 3. Verificación de captcha Cloudflare Turnstile
        if (! $this->turnstile->verify($captchaToken, $clientIp)) {
            $this->logConsulta($dni, $clientIp, ConsultaLog::RESULTADO_ERROR);

            throw ValidationException::withMessages([
                'captcha_token' => ['La verificación del captcha de seguridad falló o ha expirado.'],
            ]);
        }

        // 4. Caché efímera en memoria/store configurado con clave HMAC (nunca en BD, máx 1 hora)
        $key = (string) config('app.key', 'ConsultaHoySecretKey');
        $dniHash = hash_hmac('sha256', $dni, $key);
        $cacheKey = "dni:hash:{$dniHash}";

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $this->logConsulta($dni, $clientIp, ConsultaLog::RESULTADO_OK);

            return $cached;
        }

        // 5. Consulta al proveedor externo
        try {
            $result = $this->provider->find($dni);

            if ($result === null) {
                $this->logConsulta($dni, $clientIp, ConsultaLog::RESULTADO_NOT_FOUND);

                return null;
            }

            // 6. Minimización de datos (docs/07: solo nombres y apellidos, DNI enmascarado)
            $maskedDni = substr($dni, 0, 4).'****';

            $payload = [
                'data' => [
                    'dni_enmascarado' => $maskedDni,
                    'nombres' => $result['nombres'],
                    'apellido_paterno' => $result['apellido_paterno'],
                    'apellido_materno' => $result['apellido_materno'],
                ],
                'meta' => [
                    'actualizado_en' => now('America/Lima')->toAtomString(),
                    'fuente' => $result['fuente'],
                    'stale' => false,
                ],
            ];

            // Almacenar en caché máx 1 hora
            Cache::put($cacheKey, $payload, self::CACHE_TTL_SECONDS);

            // Auditoría segura y registro de salud
            $this->logConsulta($dni, $clientIp, ConsultaLog::RESULTADO_OK);
            $this->recordSourceHealthSuccess();

            return $payload;
        } catch (Throwable $e) {
            $this->logConsulta($dni, $clientIp, ConsultaLog::RESULTADO_ERROR);
            $this->recordSourceHealthError($e->getMessage());

            throw new SourceUnavailableException(
                'El servicio de consulta DNI no está disponible en este momento.',
                previous: $e
            );
        }
    }

    /**
     * Registra la auditoría en consultas_log con HMAC-SHA256 (nunca texto plano, docs/07).
     */
    private function logConsulta(string $documento, ?string $ip, string $resultado): void
    {
        try {
            $key = (string) config('app.key', 'ConsultaHoySecretKey');
            $docHash = hash_hmac('sha256', $documento, $key);
            $ipHash = hash_hmac('sha256', $ip ?? '127.0.0.1', $key);

            ConsultaLog::query()->create([
                'tipo' => ConsultaLog::TIPO_DNI,
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
            ['modulo' => 'dni'],
            [
                'ultima_ok' => now('America/Lima'),
                'intentos_fallidos' => 0,
                'mensaje_error' => null,
            ]
        );
    }

    private function recordSourceHealthError(string $errorMessage): void
    {
        $estado = FuenteEstado::query()->firstOrNew(['modulo' => 'dni']);
        $estado->ultimo_error = now('America/Lima');
        $estado->mensaje_error = $errorMessage;
        $estado->intentos_fallidos = ($estado->intentos_fallidos ?? 0) + 1;
        $estado->save();
    }
}
