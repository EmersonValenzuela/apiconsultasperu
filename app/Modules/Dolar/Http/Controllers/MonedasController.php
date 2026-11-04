<?php

namespace App\Modules\Dolar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dolar\Http\Requests\ConvertirMonedasRequest;
use App\Modules\Dolar\Services\DolarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MonedasController extends Controller
{
    /**
     * Convierte un monto entre dos divisas soportadas.
     */
    public function convertir(ConvertirMonedasRequest $request, DolarService $service): JsonResponse
    {
        $result = $service->convertir(
            (string) $request->validated('de'),
            (string) $request->validated('a'),
            (float) $request->validated('monto')
        );

        return ApiResponse::success($result['data'], $result['meta']);
    }
}
