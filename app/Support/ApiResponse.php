<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Envelope estándar de la API (docs/03-api-v1.md y docs/02-arquitectura.md).
 *
 * Éxito: { "data": ..., "meta": { ... } }
 * Error: { "error": { "codigo": ..., "mensaje": ..., "detalles"?: ... } }
 */
class ApiResponse
{
    public const ERROR_VALIDATION = 'validacion_fallida';

    public const ERROR_NOT_FOUND = 'no_encontrado';

    public const ERROR_RATE_LIMITED = 'limite_excedido';

    public const ERROR_SOURCE_UNAVAILABLE = 'fuente_no_disponible';

    public const ERROR_INTERNAL = 'error_interno';

    /**
     * Respuesta exitosa con el envelope estándar.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function success(mixed $data, array $meta = [], int $status = 200, array $headers = []): JsonResponse
    {
        $body = ['data' => $data];

        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return self::json($body, $status, $headers);
    }

    /**
     * Respuesta exitosa para datos de una fuente externa: exige actualizado_en y fuente.
     *
     * @param  array<string, mixed>  $extraMeta
     */
    public static function fromSource(
        mixed $data,
        \DateTimeInterface|string $updatedAt,
        string $source,
        bool $stale = false,
        array $extraMeta = [],
    ): JsonResponse {
        $updatedAt = $updatedAt instanceof \DateTimeInterface
            ? $updatedAt->format(DATE_ATOM)
            : $updatedAt;

        return self::success($data, array_merge([
            'actualizado_en' => $updatedAt,
            'fuente' => $source,
            'stale' => $stale,
        ], $extraMeta));
    }

    /**
     * Respuesta de error con el formato de docs/02.
     *
     * @param  array<string, mixed>|null  $details
     * @param  array<string, string>  $headers
     */
    public static function error(string $code, string $message, int $status, ?array $details = null, array $headers = []): JsonResponse
    {
        $error = [
            'codigo' => $code,
            'mensaje' => $message,
        ];

        if ($details !== null) {
            $error['detalles'] = $details;
        }

        return self::json(['error' => $error], $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    private static function json(array $body, int $status, array $headers): JsonResponse
    {
        return new JsonResponse(
            $body,
            $status,
            array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        );
    }
}
