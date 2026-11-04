<?php

use App\Modules\Dolar\Jobs\SyncExchangeRatesJob;
use App\Modules\Sismos\Jobs\SyncSismosJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler: Módulo Dólar (actualización cada 30 minutos)
Schedule::job(new SyncExchangeRatesJob())->everyThirtyMinutes()->name('sync-dolar-rates')->withoutOverlapping();

// Scheduler: Módulo Sismos (actualización cada 5 minutos)
Schedule::job(new SyncSismosJob())->everyFiveMinutes()->name('sync-sismos')->withoutOverlapping();

// Scheduler: Módulo Sismos - Purga semanal de eventos mayores a 1 año (docs/04)
Schedule::command('sismos:purgar')->weekly()->name('purge-old-sismos');
