<?php

namespace App\Support\Http\Middleware;

use App\Models\ClienteApi;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar la cabecera opcional X-App-Key (docs/07).
 *
 * - Si no está presente, continúa la ejecución normalmente (modo público por IP).
 * - Si está presente, valida que exista un cliente activo con ese hash SHA-256.
 *   - Si no es válido o está inactivo, responde HTTP 401.
 *   - Si es válido, asocia el cliente al request para que los limitadores usen su cuota.
 */
class ValidateAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-App-Key')) {
            $request->attributes->set('cliente_api', null);

            return $next($request);
        }

        $rawKey = trim((string) $request->header('X-App-Key'));

        if ($rawKey === '') {
            return ApiResponse::error(
                ApiResponse::ERROR_UNAUTHENTICATED,
                'La cabecera X-App-Key no puede estar vacía.',
                401
            );
        }

        $client = ClienteApi::findActiveByRawKey($rawKey);

        if ($client === null) {
            return ApiResponse::error(
                ApiResponse::ERROR_UNAUTHENTICATED,
                'La clave de aplicación provista no es válida o está inactiva.',
                401
            );
        }

        $request->attributes->set('cliente_api', $client);

        return $next($request);
    }
}
