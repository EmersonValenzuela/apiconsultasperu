<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Modules\Calculadoras\Http\Controllers\CalculadorasController;
use App\Modules\Dolar\Http\Controllers\DolarController;
use App\Modules\Dolar\Http\Controllers\MonedasController;
use App\Modules\Sismos\Http\Controllers\SismosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Consulta Hoy Perú
|--------------------------------------------------------------------------
| Contrato: docs/03-api-v1.md. Prefijo final: /api/v1.
*/

Route::prefix('v1')->name('v1.')->group(function () {
    // Sistema
    Route::get('health', HealthController::class)->name('health');

    // Módulo 1: Dólar y monedas
    Route::prefix('dolar')->name('dolar.')->group(function () {
        Route::get('/', [DolarController::class, 'actual'])
            ->middleware('cache.public:1800')
            ->name('actual');

        Route::get('historico', [DolarController::class, 'historico'])
            ->middleware('cache.public:1800')
            ->name('historico');
    });

    Route::prefix('monedas')->name('monedas.')->group(function () {
        Route::get('convertir', [MonedasController::class, 'convertir'])
            ->middleware('cache.public:1800')
            ->name('convertir');
    });

    // Módulo 2: Sismos
    Route::prefix('sismos')->name('sismos.')->group(function () {
        Route::get('/', [SismosController::class, 'index'])
            ->middleware('cache.public:300')
            ->name('index');

        Route::get('{id}', [SismosController::class, 'show'])
            ->whereNumber('id')
            ->middleware('cache.public:300')
            ->name('show');
    });

    // Módulo 3: Calculadoras (límite 30/min según docs/07)
    Route::prefix('calculadoras')->name('calculadoras.')->middleware('throttle:calculadoras')->group(function () {
        Route::post('igv', [CalculadorasController::class, 'igv'])->name('igv');
        Route::post('gratificacion', [CalculadorasController::class, 'gratificacion'])->name('gratificacion');
        Route::post('cts', [CalculadorasController::class, 'cts'])->name('cts');
    });
});
