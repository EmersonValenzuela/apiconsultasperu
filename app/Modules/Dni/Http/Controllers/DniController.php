<?php

namespace App\Modules\Dni\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dni\Http\Requests\ConsultarDniRequest;
use App\Modules\Dni\Http\Requests\ValidarDniRequest;
use App\Modules\Dni\Services\DniService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DniController extends Controller
{
    /**
     * Valida el formato de un DNI (8 dígitos) sin consultar ninguna fuente externa.
     */
    public function validar(ValidarDniRequest $request, DniService $service): JsonResponse
    {
        $result = $service->validarFormato((string) $request->validated('dni'));

        return ApiResponse::success($result);
    }

    /**
     * Consulta los datos de un DNI bajo demanda con verificación de captcha Turnstile.
     */
    public function consulta(ConsultarDniRequest $request, DniService $service): JsonResponse
    {
        $result = $service->consultar(
            (string) $request->validated('dni'),
            (string) $request->validated('captcha_token'),
            $request->ip()
        );

        if ($result === null) {
            return ApiResponse::error(
                ApiResponse::ERROR_NOT_FOUND,
                'El DNI solicitado no fue encontrado o no está vigente.',
                404
            );
        }

        $response = ApiResponse::fromSource(
            $result['data'],
            $result['meta']['actualizado_en'],
            $result['meta']['fuente'],
            $result['meta']['stale']
        );

        // Cabecera estricta: datos personales no deben ser cacheados por navegadores ni CDNs (docs/07)
        $response->header('Cache-Control', 'private, no-cache, no-store, must-revalidate');

        return $response;
    }
}
