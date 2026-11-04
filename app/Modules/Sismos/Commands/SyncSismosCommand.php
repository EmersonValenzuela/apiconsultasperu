<?php

namespace App\Modules\Sismos\Commands;

use App\Modules\Sismos\Services\SismosService;
use Illuminate\Console\Command;
use Throwable;

class SyncSismosCommand extends Command
{
    protected $signature = 'sismos:sincronizar {--dias=7 : Días de historial a consultar} {--min=0 : Magnitud mínima}';

    protected $description = 'Sincroniza los sismos recientes desde USGS';

    public function handle(SismosService $service): int
    {
        $dias = (int) $this->option('dias');
        $min = (float) $this->option('min');

        $this->info("Consultando sismos en USGS (últimos {$dias} días, magnitud >= {$min})...");

        try {
            $count = $service->syncSismos($dias, $min);
            $this->info("Sincronización completada. Se procesaron {$count} sismos.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Error al sincronizar sismos: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
