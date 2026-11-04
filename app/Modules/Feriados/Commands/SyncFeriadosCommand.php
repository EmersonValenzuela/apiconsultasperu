<?php

namespace App\Modules\Feriados\Commands;

use App\Modules\Feriados\Services\FeriadosService;
use Illuminate\Console\Command;
use Throwable;

class SyncFeriadosCommand extends Command
{
    protected $signature = 'feriados:sincronizar {anio? : Año a sincronizar (opcional)}';

    protected $description = 'Sincroniza feriados desde Nager.Date respetando los feriados manuales';

    public function handle(FeriadosService $service): int
    {
        $anio = $this->argument('anio') ? (int) $this->argument('anio') : (int) now('America/Lima')->format('Y');

        $this->info("Sincronizando feriados del año {$anio} desde Nager.Date...");

        try {
            $count = $service->syncHolidays($anio);
            $this->info("Sincronización completada. Se procesaron {$count} feriados.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Error al sincronizar feriados: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
