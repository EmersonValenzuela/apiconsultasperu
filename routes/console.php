<?php

use App\Modules\Dolar\Jobs\SyncExchangeRatesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler: Módulo Dólar (actualización cada 30 minutos)
Schedule::job(new SyncExchangeRatesJob())->everyThirtyMinutes()->name('sync-dolar-rates')->withoutOverlapping();
