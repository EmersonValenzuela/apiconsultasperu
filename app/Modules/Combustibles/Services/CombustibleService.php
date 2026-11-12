<?php

namespace App\Modules\Combustibles\Services;

use App\Modules\Combustibles\Models\PrecioCombustible;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Support\Facades\Cache;

class CombustibleService
{
    private const CACHE_TTL_SECONDS = 1800; // 30 minutos

    /**
     * Devuelve el listado de precios de combustibles para la región solicitada.
     *
     * @return array<string, mixed>
     */
    public function obtenerPrecios(?string $region = null): array
    {
        $regionBuscada = $region ?: 'Lima y Callao';
        $cacheKey = 'combustibles:precios:' . md5($regionBuscada);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($regionBuscada) {
            $combustibles = PrecioCombustible::where('region', $regionBuscada)
                ->orderBy('id', 'asc')
                ->get();

            if ($combustibles->isEmpty()) {
                // Si no hay para la región específica, fallback a Lima y Callao
                $combustibles = PrecioCombustible::where('region', 'Lima y Callao')
                    ->orderBy('id', 'asc')
                    ->get();
            }

            return $combustibles->toArray();
        });
    }

    /**
     * Calcula costos de llenado de tanque o volumen obtenido según presupuesto.
     *
     * @return array<string, mixed>
     */
    public function calcularTanque(string $tipo, float $cantidad, string $tipoCalculo = 'llenar_tanque'): array
    {
        $combustible = PrecioCombustible::where('tipo', $tipo)->first();

        if (! $combustible) {
            throw new SourceUnavailableException("No hay precios registrados para el tipo de combustible '{$tipo}'.");
        }

        if ($combustible->precio_promedio <= 0 || $combustible->precio_minimo <= 0 || $combustible->precio_maximo <= 0) {
            throw new SourceUnavailableException("Los precios registrados para '{$tipo}' no son válidos.");
        }

        if ($tipoCalculo === 'llenar_tanque') {
            $costoPromedio = round($cantidad * $combustible->precio_promedio, 2);
            $costoMinimo = round($cantidad * $combustible->precio_minimo, 2);
            $costoMaximo = round($cantidad * $combustible->precio_maximo, 2);
            $ahorro = round($costoMaximo - $costoMinimo, 2);

            return [
                'tipo' => $combustible->tipo,
                'nombre' => $combustible->nombre,
                'unidad' => $combustible->unidad,
                'simbolo_unidad' => $combustible->simbolo_unidad,
                'tipo_calculo' => 'llenar_tanque',
                'cantidad_tanque' => $cantidad,
                'costo_promedio' => $costoPromedio,
                'costo_minimo' => $costoMinimo,
                'costo_maximo' => $costoMaximo,
                'ahorro_potencial' => $ahorro,
                'precio_unitario_promedio' => $combustible->precio_promedio,
                'precio_unitario_minimo' => $combustible->precio_minimo,
                'precio_unitario_maximo' => $combustible->precio_maximo,
            ];
        }

        // Tipo cálculo: presupuesto_soles
        $volumenPromedio = round($cantidad / $combustible->precio_promedio, 2);
        $volumenMinimo = round($cantidad / $combustible->precio_maximo, 2);
        $volumenMaximo = round($cantidad / $combustible->precio_minimo, 2);
        $volumenExtra = round($volumenMaximo - $volumenPromedio, 2);

        return [
            'tipo' => $combustible->tipo,
            'nombre' => $combustible->nombre,
            'unidad' => $combustible->unidad,
            'simbolo_unidad' => $combustible->simbolo_unidad,
            'tipo_calculo' => 'presupuesto_soles',
            'presupuesto_soles' => $cantidad,
            'volumen_promedio' => $volumenPromedio,
            'volumen_minimo' => $volumenMinimo,
            'volumen_maximo' => $volumenMaximo,
            'volumen_extra' => $volumenExtra,
            'precio_unitario_promedio' => $combustible->precio_promedio,
            'precio_unitario_minimo' => $combustible->precio_minimo,
            'precio_unitario_maximo' => $combustible->precio_maximo,
        ];
    }
}
