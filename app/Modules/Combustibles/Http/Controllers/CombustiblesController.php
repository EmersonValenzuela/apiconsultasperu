<?php

namespace App\Modules\Combustibles\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Combustibles\Http\Requests\CalcularTanqueRequest;
use App\Modules\Combustibles\Services\CombustibleService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CombustiblesController extends Controller
{
    public function __construct(
        private readonly CombustibleService $service
    ) {
    }

    /**
     * Lista los precios de referencia de combustibles en el Perú.
     */
    public function index(Request $request): JsonResponse
    {
        $region = $request->query('region');
        $precios = $this->service->obtenerPrecios($region);

        $ultimaVigencia = ! empty($precios)
            ? ($precios[0]['vigencia_desde'] ?? Carbon::now('America/Lima')->toDateString())
            : Carbon::now('America/Lima')->toDateString();
        $regionEfectiva = $precios[0]['region'] ?? ($region ?: 'Lima y Callao');

        return ApiResponse::success(
            $precios,
            [
                'actualizado_en' => Carbon::now('America/Lima')->toIso8601String(),
                'fuente' => 'Osinergmin / Facilito / Petroperú',
                'vigencia' => $ultimaVigencia,
                'region' => $regionEfectiva,
                'aviso' => 'Precios promedio y rangos referenciales de estaciones de servicio registradas.',
            ]
        );
    }

    /**
     * Calcula el costo de llenado o volumen obtenido según vehículo o presupuesto.
     */
    public function calcularTanque(CalcularTanqueRequest $request): JsonResponse
    {
        $resultado = $this->service->calcularTanque(
            $request->validated('tipo'),
            (float) $request->validated('cantidad'),
            $request->validated('tipo_calculo', 'llenar_tanque')
        );

        return ApiResponse::success(
            $resultado,
            [
                'calculado_en' => Carbon::now('America/Lima')->toIso8601String(),
                'fuente' => 'Precios referenciales Osinergmin / Facilito',
                'referencial' => true,
            ]
        );
    }
}
