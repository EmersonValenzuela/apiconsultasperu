<?php

namespace App\Modules\Sismos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sismos\Http\Requests\ListarSismosRequest;
use App\Modules\Sismos\Services\SismosService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SismosController extends Controller
{
    /**
     * Lista los últimos sismos en Perú con filtros por magnitud, días y límite.
     */
    public function index(ListarSismosRequest $request, SismosService $service): JsonResponse
    {
        $minMagnitud = (float) $request->input('min_magnitud', 0);
        $dias = (int) $request->input('dias', 7);
        $limite = (int) $request->input('limite', 50);

        $result = $service->getSismos($minMagnitud, $dias, $limite);

        return ApiResponse::success($result['data'], $result['meta']);
    }

    /**
     * Detalle de un sismo específico por ID.
     */
    public function show(int $id, SismosService $service): JsonResponse
    {
        $result = $service->getSismo($id);

        return ApiResponse::success($result['data'], $result['meta']);
    }
}
