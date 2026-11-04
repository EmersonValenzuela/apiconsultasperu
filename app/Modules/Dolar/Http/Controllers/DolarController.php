<?php

namespace App\Modules\Dolar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dolar\Http\Requests\HistoricoDolarRequest;
use App\Modules\Dolar\Services\DolarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DolarController extends Controller
{
    /**
     * Cotización actual del dólar frente al sol.
     */
    public function actual(DolarService $service): JsonResponse
    {
        $result = $service->getDolarActual();

        return ApiResponse::success($result['data'], $result['meta']);
    }

    /**
     * Histórico de cotizaciones del dólar en un rango de fechas (máx. 366 días).
     */
    public function historico(HistoricoDolarRequest $request, DolarService $service): JsonResponse
    {
        $result = $service->getHistorico(
            (string) $request->validated('desde'),
            (string) $request->validated('hasta'),
            (string) $request->validated('moneda', 'USD')
        );

        return ApiResponse::success($result['data'], $result['meta']);
    }
}
