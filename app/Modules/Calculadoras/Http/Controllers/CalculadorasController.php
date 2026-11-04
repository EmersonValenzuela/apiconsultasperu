<?php

namespace App\Modules\Calculadoras\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calculadoras\Http\Requests\CalcularCtsRequest;
use App\Modules\Calculadoras\Http\Requests\CalcularGratificacionRequest;
use App\Modules\Calculadoras\Http\Requests\CalcularIgvRequest;
use App\Modules\Calculadoras\Services\CalculadoraService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CalculadorasController extends Controller
{
    /**
     * Calcula el IGV desagregado o total (referencial).
     */
    public function igv(CalcularIgvRequest $request, CalculadoraService $service): JsonResponse
    {
        $result = $service->calcularIgv(
            monto: (float) $request->validated('monto'),
            tipo: (string) $request->validated('tipo'),
        );

        return ApiResponse::success($result, ['referencial' => true]);
    }

    /**
     * Calcula la gratificación legal y bonificación extraordinaria (referencial).
     */
    public function gratificacion(CalcularGratificacionRequest $request, CalculadoraService $service): JsonResponse
    {
        $result = $service->calcularGratificacion(
            sueldo: (float) $request->validated('sueldo'),
            asignacionFamiliar: (bool) $request->validated('asignacion_familiar', false),
            mesesTrabajados: (int) $request->validated('meses_trabajados'),
            promedioVariables: (float) $request->validated('promedio_variables', 0),
            tipoSeguro: (string) $request->validated('tipo_seguro'),
        );

        return ApiResponse::success($result, ['referencial' => true]);
    }

    /**
     * Calcula la CTS semestral por meses y días (referencial).
     */
    public function cts(CalcularCtsRequest $request, CalculadoraService $service): JsonResponse
    {
        $result = $service->calcularCts(
            sueldo: (float) $request->validated('sueldo'),
            asignacionFamiliar: (bool) $request->validated('asignacion_familiar', false),
            ultimaGratificacion: (float) $request->validated('ultima_gratificacion', 0),
            meses: (int) $request->validated('meses'),
            dias: (int) $request->validated('dias'),
            promedioVariables: (float) $request->validated('promedio_variables', 0),
        );

        return ApiResponse::success($result, ['referencial' => true]);
    }
}
