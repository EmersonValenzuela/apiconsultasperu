<?php

use Database\Seeders\ParametrosSeeder;

beforeEach(function () {
    $this->seed(ParametrosSeeder::class);
});

test('POST /api/v1/calculadoras/igv calcula correctamente y devuelve meta.referencial', function () {
    $response = $this->postJson('/api/v1/calculadoras/igv', [
        'monto' => 1000,
        'tipo' => 'base',
    ]);

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertJson([
            'data' => [
                'base' => 1000.0,
                'igv' => 180.0,
                'total' => 1180.0,
                'tasa' => 0.18,
            ],
            'meta' => [
                'referencial' => true,
            ],
        ]);
});

test('POST /api/v1/calculadoras/igv valida datos incorrectos con 422', function () {
    $this->postJson('/api/v1/calculadoras/igv', [
        'monto' => -50,
        'tipo' => 'invalido',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida')
        ->assertJsonStructure(['error' => ['detalles' => ['monto', 'tipo']]]);
});

test('POST /api/v1/calculadoras/gratificacion calcula correctamente con meta.referencial', function () {
    $response = $this->postJson('/api/v1/calculadoras/gratificacion', [
        'sueldo' => 3000,
        'asignacion_familiar' => false,
        'meses_trabajados' => 6,
        'promedio_variables' => 0,
        'tipo_seguro' => 'essalud',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'remuneracion_computable' => 3000.0,
                'gratificacion' => 3000.0,
                'bonificacion_extraordinaria' => 270.0,
                'total' => 3270.0,
            ],
            'meta' => [
                'referencial' => true,
            ],
        ]);
});

test('POST /api/v1/calculadoras/gratificacion valida meses fuera de rango 1-6 con 422', function () {
    $this->postJson('/api/v1/calculadoras/gratificacion', [
        'sueldo' => 3000,
        'meses_trabajados' => 7,
        'tipo_seguro' => 'essalud',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');
});

test('POST /api/v1/calculadoras/cts calcula correctamente con meta.referencial', function () {
    $response = $this->postJson('/api/v1/calculadoras/cts', [
        'sueldo' => 3000,
        'asignacion_familiar' => false,
        'ultima_gratificacion' => 3000,
        'meses' => 6,
        'dias' => 0,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'remuneracion_computable' => 3500.0,
                'cts_por_meses' => 1750.0,
                'cts_por_dias' => 0.0,
                'total' => 1750.0,
            ],
            'meta' => [
                'referencial' => true,
            ],
        ]);
});

test('POST /api/v1/calculadoras/cts valida días fuera de rango 0-29 con 422', function () {
    $this->postJson('/api/v1/calculadoras/cts', [
        'sueldo' => 3000,
        'meses' => 3,
        'dias' => 30,
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');
});
