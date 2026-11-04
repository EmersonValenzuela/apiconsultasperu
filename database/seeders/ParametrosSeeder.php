<?php

namespace Database\Seeders;

use App\Modules\Calculadoras\Models\Parametro;
use Illuminate\Database\Seeder;

class ParametrosSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            [
                'clave' => 'igv_tasa',
                'valor' => '0.1800',
                'vigente_desde' => '2011-03-01',
                'vigente_hasta' => null,
                'fuente_legal' => 'D.S. N° 055-99-EF (TUO Ley del IGV e IPM)',
                'verificado_en' => '2026-11-04',
            ],
            [
                'clave' => 'rmv',
                'valor' => '1025.0000',
                'vigente_desde' => '2022-05-01',
                'vigente_hasta' => null,
                'fuente_legal' => 'D.S. N° 003-2022-TR',
                'verificado_en' => '2026-11-04',
            ],
            [
                'clave' => 'asignacion_familiar_pct',
                'valor' => '0.1000',
                'vigente_desde' => '1989-12-06',
                'vigente_hasta' => null,
                'fuente_legal' => 'Ley N° 25129',
                'verificado_en' => '2026-11-04',
            ],
            [
                'clave' => 'bono_extra_essalud_pct',
                'valor' => '0.0900',
                'vigente_desde' => '2015-06-25',
                'vigente_hasta' => null,
                'fuente_legal' => 'Ley N° 30334',
                'verificado_en' => '2026-11-04',
            ],
            [
                'clave' => 'bono_extra_eps_pct',
                'valor' => '0.0675',
                'vigente_desde' => '2015-06-25',
                'vigente_hasta' => null,
                'fuente_legal' => 'Ley N° 30334',
                'verificado_en' => '2026-11-04',
            ],
            [
                'clave' => 'uit',
                'valor' => '5350.0000',
                'vigente_desde' => '2026-01-01',
                'vigente_hasta' => null,
                'fuente_legal' => 'D.S. N° 309-2023-EF / valor anual',
                'verificado_en' => '2026-11-04',
            ],
        ];

        foreach ($parametros as $param) {
            Parametro::query()->updateOrCreate(
                [
                    'clave' => $param['clave'],
                    'vigente_desde' => $param['vigente_desde'],
                ],
                $param
            );
        }
    }
}
