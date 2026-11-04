<?php

namespace App\Modules\Ruc\Commands;

use App\Modules\Ruc\Services\RucService;
use Illuminate\Console\Command;

class PurgeConsultasLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consultas:purgar {--days=90 : Número de días de retención}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purga los registros anónimos de consultas_log mayores a 90 días (docs/04)';

    /**
     * Execute the console command.
     */
    public function handle(RucService $service): int
    {
        $days = (int) $this->option('days');
        $this->info("Purgando registros de consultas_log con más de {$days} días de antigüedad...");

        $deleted = $service->purgeOldLogs($days);

        $this->info("Se eliminaron {$deleted} registros antiguos de auditoría.");

        return Command::SUCCESS;
    }
}
