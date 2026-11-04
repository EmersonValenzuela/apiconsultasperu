<?php

namespace App\Modules\Feriados\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feriados\Http\Requests\ListarFeriadosRequest;
use App\Modules\Feriados\Services\FeriadosService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FeriadosController extends Controller
{
    /**
     * Lista los feriados y días no laborables del año especificado.
     */
    public function index(ListarFeriadosRequest $request, FeriadosService $service): JsonResponse
    {
        $anio = (int) $request->input('anio', now('America/Lima')->year);
        $result = $service->getFeriados($anio);

        return ApiResponse::success($result['data'], $result['meta']);
    }

    /**
     * Devuelve el próximo feriado a partir de hoy y los días que faltan.
     */
    public function proximo(FeriadosService $service): JsonResponse
    {
        $result = $service->getProximoFeriado();

        return ApiResponse::success($result['data'], $result['meta']);
    }
}
