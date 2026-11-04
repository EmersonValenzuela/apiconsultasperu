<?php

use App\Modules\Calculadoras\Models\Parametro;
use App\Modules\Calculadoras\Services\CalculadoraService;
use Database\Seeders\ParametrosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ParametrosSeeder::class);
    $this->service = new CalculadoraService();
});

test('Caso 1: IGV base 1000, tasa 0.18 da igv 180.00 y total 1180.00', function () {
    $res = $this->service->calcularIgv(1000, 'base');

    expect($res['base'])->toBe(1000.0)
        ->and($res['igv'])->toBe(180.0)
        ->and($res['total'])->toBe(1180.0)
        ->and($res['tasa'])->toBe(0.18);
});

test('Caso 2: IGV total 1180, tasa 0.18 da base 1000.00 e igv 180.00', function () {
    $res = $this->service->calcularIgv(1180, 'total');

    expect($res['base'])->toBe(1000.0)
        ->and($res['igv'])->toBe(180.0)
        ->and($res['total'])->toBe(1180.0);
});

test('Caso 3: Gratificación sueldo 3000, sin asignación, 6 meses, EsSalud (9%) da 3000.00, bono 270.00, total 3270.00', function () {
    $res = $this->service->calcularGratificacion(
        sueldo: 3000,
        asignacionFamiliar: false,
        mesesTrabajados: 6,
        promedioVariables: 0,
        tipoSeguro: 'essalud'
    );

    expect($res['remuneracion_computable'])->toBe(3000.0)
        ->and($res['gratificacion'])->toBe(3000.0)
        ->and($res['bonificacion_extraordinaria'])->toBe(270.0)
        ->and($res['total'])->toBe(3270.0);
});

test('Caso 4: Gratificación sueldo 3000, 4 meses, EsSalud (9%) da 2000.00, bono 180.00, total 2180.00', function () {
    $res = $this->service->calcularGratificacion(
        sueldo: 3000,
        asignacionFamiliar: false,
        mesesTrabajados: 4,
        promedioVariables: 0,
        tipoSeguro: 'essalud'
    );

    expect($res['remuneracion_computable'])->toBe(3000.0)
        ->and($res['gratificacion'])->toBe(2000.0)
        ->and($res['bonificacion_extraordinaria'])->toBe(180.0)
        ->and($res['total'])->toBe(2180.0);
});

test('Gratificación con EPS (6.75%) aplica la tasa correcta de parámetros', function () {
    $res = $this->service->calcularGratificacion(
        sueldo: 3000,
        asignacionFamiliar: false,
        mesesTrabajados: 6,
        promedioVariables: 0,
        tipoSeguro: 'eps'
    );

    expect($res['gratificacion'])->toBe(3000.0)
        ->and($res['bonificacion_extraordinaria'])->toBe(202.50)
        ->and($res['total'])->toBe(3202.50);
});

test('Caso 5: CTS sueldo 3000, última gratificación 3000, 6 meses, 0 días da RC_cts 3500.00 y total 1750.00', function () {
    $res = $this->service->calcularCts(
        sueldo: 3000,
        asignacionFamiliar: false,
        ultimaGratificacion: 3000,
        meses: 6,
        dias: 0
    );

    expect($res['remuneracion_computable'])->toBe(3500.0)
        ->and($res['cts_por_meses'])->toBe(1750.0)
        ->and($res['cts_por_dias'])->toBe(0.0)
        ->and($res['total'])->toBe(1750.0);
});

test('Caso 6: CTS sueldo 3000, última gratificación 3000, 3 meses, 15 días da cts_meses 875.00, cts_dias 145.83 y total 1020.83', function () {
    $res = $this->service->calcularCts(
        sueldo: 3000,
        asignacionFamiliar: false,
        ultimaGratificacion: 3000,
        meses: 3,
        dias: 15
    );

    expect($res['remuneracion_computable'])->toBe(3500.0)
        ->and($res['cts_por_meses'])->toBe(875.0)
        ->and($res['cts_por_dias'])->toBe(145.83)
        ->and($res['total'])->toBe(1020.83);
});

test('rechaza el cálculo si falta un parámetro requerido en la base de datos', function () {
    Parametro::query()->where('clave', 'igv_tasa')->delete();

    expect(fn () => $this->service->calcularIgv(1000, 'base'))
        ->toThrow(RuntimeException::class);
});
