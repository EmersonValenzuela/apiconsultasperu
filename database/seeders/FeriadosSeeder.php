<?php

namespace Database\Seeders;

use App\Modules\Feriados\Models\Feriado;
use Illuminate\Database\Seeder;

class FeriadosSeeder extends Seeder
{
    public function run(): void
    {
        // 16 feriados nacionales de Perú para el año 2026
        $feriados = [
            ['fecha' => '2026-01-01', 'nombre' => 'Año Nuevo', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-04-02', 'nombre' => 'Jueves Santo', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-04-03', 'nombre' => 'Viernes Santo', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-05-01', 'nombre' => 'Día del Trabajo', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-06-07', 'nombre' => 'Batalla de Arica y Día de la Bandera', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-06-29', 'nombre' => 'San Pedro y San Pablo', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-07-23', 'nombre' => 'Día de la Fuerza Aérea del Perú', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-07-28', 'nombre' => 'Fiestas Patrias', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-07-29', 'nombre' => 'Fiestas Patrias', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-08-06', 'nombre' => 'Batalla de Junín', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-08-30', 'nombre' => 'Santa Rosa de Lima', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-10-08', 'nombre' => 'Combate de Angamos', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-11-01', 'nombre' => 'Todos los Santos', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-12-08', 'nombre' => 'Inmaculada Concepción', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-12-09', 'nombre' => 'Batalla de Ayacucho', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
            ['fecha' => '2026-12-25', 'nombre' => 'Navidad', 'tipo' => 'feriado', 'anio' => 2026, 'fuente' => 'manual'],
        ];

        foreach ($feriados as $item) {
            Feriado::query()->updateOrCreate(
                [
                    'fecha' => $item['fecha'],
                    'nombre' => $item['nombre'],
                ],
                $item
            );
        }
    }
}
