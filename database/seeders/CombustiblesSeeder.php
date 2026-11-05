<?php

namespace Database\Seeders;

use App\Modules\Combustibles\Models\PrecioCombustible;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CombustiblesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hoy = Carbon::now('America/Lima')->toDateString();

        $combustibles = [
            [
                'region' => 'Lima y Callao',
                'tipo' => 'gasolina_regular',
                'nombre' => 'Gasolina Regular',
                'octanaje' => '90 / 95 (Unificada)',
                'unidad' => 'galón',
                'simbolo_unidad' => 'gal',
                'precio_promedio' => 16.29,
                'precio_minimo' => 14.79,
                'precio_maximo' => 18.50,
                'fuente' => 'Osinergmin / Facilito / Petroperú',
                'vigencia_desde' => $hoy,
            ],
            [
                'region' => 'Lima y Callao',
                'tipo' => 'gasolina_premium',
                'nombre' => 'Gasolina Premium',
                'octanaje' => '97 / 98',
                'unidad' => 'galón',
                'simbolo_unidad' => 'gal',
                'precio_promedio' => 18.49,
                'precio_minimo' => 16.99,
                'precio_maximo' => 21.20,
                'fuente' => 'Osinergmin / Facilito / Petroperú',
                'vigencia_desde' => $hoy,
            ],
            [
                'region' => 'Lima y Callao',
                'tipo' => 'diesel',
                'nombre' => 'Diésel B5 S-50 UV',
                'octanaje' => null,
                'unidad' => 'galón',
                'simbolo_unidad' => 'gal',
                'precio_promedio' => 16.19,
                'precio_minimo' => 14.89,
                'precio_maximo' => 18.10,
                'fuente' => 'Osinergmin / Facilito / Petroperú',
                'vigencia_desde' => $hoy,
            ],
            [
                'region' => 'Lima y Callao',
                'tipo' => 'glp_vehicular',
                'nombre' => 'GLP Automotor',
                'octanaje' => null,
                'unidad' => 'litro',
                'simbolo_unidad' => 'L',
                'precio_promedio' => 2.29,
                'precio_minimo' => 1.99,
                'precio_maximo' => 2.65,
                'fuente' => 'Osinergmin / Facilito',
                'vigencia_desde' => $hoy,
            ],
            [
                'region' => 'Lima y Callao',
                'tipo' => 'gnv',
                'nombre' => 'Gas Natural Vehicular (GNV)',
                'octanaje' => null,
                'unidad' => 'metro cúbico',
                'simbolo_unidad' => 'm³',
                'precio_promedio' => 1.55,
                'precio_minimo' => 1.39,
                'precio_maximo' => 1.79,
                'fuente' => 'Osinergmin / Facilito / Cálidda',
                'vigencia_desde' => $hoy,
            ],
            [
                'region' => 'Lima y Callao',
                'tipo' => 'balon_gas_10kg',
                'nombre' => 'Balón de Gas GLP Doméstico (10 kg)',
                'octanaje' => null,
                'unidad' => 'balón 10kg',
                'simbolo_unidad' => 'balón',
                'precio_promedio' => 44.50,
                'precio_minimo' => 36.00,
                'precio_maximo' => 55.00,
                'fuente' => 'Osinergmin / Facilito GLP',
                'vigencia_desde' => $hoy,
            ],
        ];

        foreach ($combustibles as $combustible) {
            PrecioCombustible::updateOrCreate(
                [
                    'region' => $combustible['region'],
                    'tipo' => $combustible['tipo'],
                ],
                $combustible
            );
        }
    }
}
