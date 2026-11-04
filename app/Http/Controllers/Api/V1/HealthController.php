<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\Health\HealthCheck;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Estado de la API y de cada módulo.
     *
     * Devuelve 200 con estado `ok` o `degradado`, y 503 con estado `error` si la base de datos no responde.
     */
    public function __invoke(HealthCheck $health): JsonResponse
    {
        $result = $health->run();

        return ApiResponse::success(
            $result,
            ['actualizado_en' => now()->toAtomString()],
            $result['estado'] === HealthCheck::STATUS_ERROR ? 503 : 200,
            ['Cache-Control' => 'no-store'],
        );
    }
}
