<?php

namespace App\Modules\Feriados\Jobs;

use App\Modules\Feriados\Services\FeriadosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncFeriadosJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public function handle(FeriadosService $service): void
    {
        $year = (int) now('America/Lima')->format('Y');

        // Sincroniza año actual y año siguiente
        $service->syncHolidays($year);
        $service->syncHolidays($year + 1);
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
