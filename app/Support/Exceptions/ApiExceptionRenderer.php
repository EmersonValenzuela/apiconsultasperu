<?php

namespace App\Support\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Convierte cualquier excepción en el formato de error de docs/02.
 * Nunca expone trazas salvo con APP_DEBUG=true.
 */
class ApiExceptionRenderer
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $this->shouldRender($request)) {
            return null;
        }

        $response = match (true) {
            $e instanceof ValidationException => ApiResponse::error(
                ApiResponse::ERROR_VALIDATION,
                $e->validator->errors()->first() ?: 'Los datos enviados no son válidos.',
                422,
                $e->errors(),
            ),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => ApiResponse::error(
                ApiResponse::ERROR_NOT_FOUND,
                'El recurso solicitado no existe.',
                404,
            ),
            $e instanceof ThrottleRequestsException => ApiResponse::error(
                ApiResponse::ERROR_RATE_LIMITED,
                'Has superado el límite de solicitudes. Intenta de nuevo en unos segundos.',
                429,
                null,
                $this->stringHeaders($e->getHeaders()),
            ),
            $e instanceof SourceUnavailableException => ApiResponse::error(
                ApiResponse::ERROR_SOURCE_UNAVAILABLE,
                $e->getMessage() ?: 'La fuente de datos no está disponible en este momento.',
                503,
            ),
            $e instanceof AuthenticationException => ApiResponse::error(
                'no_autenticado',
                'No autenticado.',
                401,
            ),
            $e instanceof HttpExceptionInterface => $this->fromHttpException($e),
            default => ApiResponse::error(
                ApiResponse::ERROR_INTERNAL,
                'Ocurrió un error interno. Intenta de nuevo más tarde.',
                500,
            ),
        };

        return $this->withDebug($response, $e);
    }

    private function shouldRender(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private function fromHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();

        [$code, $message] = match (true) {
            $status === 404 => [ApiResponse::ERROR_NOT_FOUND, 'El recurso solicitado no existe.'],
            $status === 429 => [ApiResponse::ERROR_RATE_LIMITED, 'Has superado el límite de solicitudes.'],
            $status === 503 => [ApiResponse::ERROR_SOURCE_UNAVAILABLE, 'El servicio no está disponible en este momento.'],
            $status === 405 => ['metodo_no_permitido', 'Método HTTP no permitido para esta ruta.'],
            $status >= 500 => [ApiResponse::ERROR_INTERNAL, 'Ocurrió un error interno. Intenta de nuevo más tarde.'],
            default => ['solicitud_invalida', 'La solicitud no es válida.'],
        };

        return ApiResponse::error($code, $message, $status, null, $this->stringHeaders($e->getHeaders()));
    }

    private function withDebug(JsonResponse $response, Throwable $e): JsonResponse
    {
        if (! config('app.debug')) {
            return $response;
        }

        $data = $response->getData(true);
        $data['error']['debug'] = [
            'excepcion' => $e::class,
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile().':'.$e->getLine(),
            'traza' => collect($e->getTrace())->take(15)->map(
                fn (array $frame) => ($frame['file'] ?? '?').':'.($frame['line'] ?? '?')
            )->all(),
        ];

        return $response->setData($data);
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    private function stringHeaders(array $headers): array
    {
        return array_map(fn ($value) => (string) $value, $headers);
    }
}
