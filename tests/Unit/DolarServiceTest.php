<?php

use App\Modules\Dolar\Contracts\ExchangeRateProvider;
use App\Modules\Dolar\Services\DolarService;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('convertir calcula correctamente entre divisas soportadas', function () {
    $providerMock = Mockery::mock(ExchangeRateProvider::class);
    $providerMock->shouldReceive('getRates')->with('USD')->andReturn([
        'fuente' => 'open.er-api.com',
        'base' => 'USD',
        'fecha' => '2026-11-04',
        'actualizado_en' => Carbon::parse('2026-11-04T00:00:00-05:00'),
        'tipo' => 'referencial',
        'tasas' => [
            'USD' => 1.0,
            'PEN' => 3.7500,
            'EUR' => 0.9000,
        ],
    ]);

    $service = new DolarService($providerMock);

    // USD a PEN
    $resUsdToPen = $service->convertir('USD', 'PEN', 100);
    expect($resUsdToPen['data']['resultado'])->toBe(375.0)
        ->and($resUsdToPen['data']['tasa'])->toBe(3.75);

    // PEN a USD
    $resPenToUsd = $service->convertir('PEN', 'USD', 375);
    expect($resPenToUsd['data']['resultado'])->toBe(100.0)
        ->and($resPenToUsd['data']['tasa'])->toBe(0.2667);

    // EUR a PEN: (3.75 / 0.90) * 100 = 416.67
    $resEurToPen = $service->convertir('EUR', 'PEN', 100);
    expect($resEurToPen['data']['resultado'])->toBe(416.67);
});

test('convertir lanza SourceUnavailableException si la divisa no existe en las tasas', function () {
    $providerMock = Mockery::mock(ExchangeRateProvider::class);
    $providerMock->shouldReceive('getRates')->with('USD')->andReturn([
        'fuente' => 'open.er-api.com',
        'base' => 'USD',
        'fecha' => '2026-11-04',
        'actualizado_en' => Carbon::parse('2026-11-04T00:00:00-05:00'),
        'tipo' => 'referencial',
        'tasas' => [
            'USD' => 1.0,
            'PEN' => 3.75,
        ],
    ]);

    $service = new DolarService($providerMock);

    expect(fn () => $service->convertir('USD', 'GBP', 100))
        ->toThrow(SourceUnavailableException::class);
});
