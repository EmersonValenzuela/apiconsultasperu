<?php

namespace App\Modules\Dni\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TurnstileVerifier
{
    /**
     * Valida el token de Cloudflare Turnstile con la API de siteverify (docs/07).
     */
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        if (config('services.dni.turnstile.bypass_in_testing') === true) {
            return true;
        }

        $secret = config('services.dni.turnstile.secret_key');
        $verifyUrl = config('services.dni.turnstile.verify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');

        // Si no hay clave secreta configurada (ej. en desarrollo local inicial)
        if (empty($secret)) {
            // Permitir en local/testing con token de pruebas de Cloudflare:
            // "1x0000000000000000000000000000000AA" es el dummy token de Cloudflare para pase siempre exitoso
            return $token === '1x0000000000000000000000000000000AA' || app()->environment('local', 'testing');
        }

        try {
            $payload = [
                'secret' => $secret,
                'response' => $token,
            ];

            if (! empty($remoteIp)) {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->timeout(5)
                ->post($verifyUrl, $payload);

            if (! $response->successful()) {
                return false;
            }

            $result = $response->json();

            return is_array($result) && ! empty($result['success']);
        } catch (Throwable) {
            return false;
        }
    }
}
