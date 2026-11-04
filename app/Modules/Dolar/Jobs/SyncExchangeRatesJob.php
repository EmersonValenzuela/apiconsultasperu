<?php

namespace App\Modules\Dolar\Jobs;

use App\Modules\Dolar\Services\DolarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncExchangeRatesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public function handle(DolarService $service): void
    {
        $service->syncRates();
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
