<?php

namespace App\Modules\Sismos\Jobs;

use App\Modules\Sismos\Services\SismosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncSismosJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public function handle(SismosService $service): void
    {
        $service->syncSismos();
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
