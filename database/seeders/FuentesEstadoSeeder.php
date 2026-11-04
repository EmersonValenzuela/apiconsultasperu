<?php

namespace Database\Seeders;

use App\Models\FuenteEstado;
use Illuminate\Database\Seeder;

/**
 * Siembra una fila por módulo en fuentes_estado. Idempotente.
 */
class FuentesEstadoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FuenteEstado::MODULES as $module) {
            FuenteEstado::query()->firstOrCreate(['modulo' => $module]);
        }
    }
}
