<?php

namespace Database\Seeders;

use App\Modules\Dolar\Models\CotizacionMoneda;
use Illuminate\Database\Seeder;

class DolarHistoricoSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 90; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $variation = (sin($i / 5) * 0.02) + (cos($i / 3) * 0.015);
            $rate = round(3.45 + $variation, 3);
            CotizacionMoneda::query()->updateOrCreate(
                ['moneda' => 'USD', 'fecha' => $date],
                [
                    'compra' => round($rate - 0.005, 3),
                    'venta' => round($rate + 0.005, 3),
                    'fuente' => 'open.er-api.com',
                    'tipo' => 'interbancario',
                    'obtenido_en' => now(),
                ]
            );
        }
    }
}
