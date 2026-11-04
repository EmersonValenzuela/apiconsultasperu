<?php

namespace App\Modules\Sismos\Commands;

use App\Modules\Sismos\Services\SismosService;
use Illuminate\Console\Command;

class PurgeSismosCommand extends Command
{
    protected $signature = 'sismos:purgar {--dias=365 : Días de retención}';

    protected $description = 'Elimina sismos con más de 1 año de antigüedad según la política de retención';

    public function handle(SismosService $service): int
    {
        $dias = (int) $this->option('dias');
        $this->info("Purgando sismos con más de {$dias} días de antigüedad...");

        $deleted = $service->purgeOldSismos($dias);
        $this->info("Purga completada. Se eliminaron {$deleted} registros.");

        return self::SUCCESS;
    }
}
