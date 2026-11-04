<?php

use App\Models\FuenteEstado;
use App\Support\Health\HealthCheck;
use Database\Seeders\FuentesEstadoSeeder;

beforeEach(function () {
    $this->seed(FuentesEstadoSeeder::class);
});

test('health endpoint responde 200 y formato envelope estándar cuando todo está bien', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertJsonStructure([
            'data' => [
                'estado',
                'base_de_datos' => ['ok', 'conexion'],
                'cache' => ['ok', 'store'],
                'modulos' => [
                    'dolar' => ['ultima_ok', 'error'],
                    'sismos' => ['ultima_ok', 'error'],
                    'feriados' => ['ultima_ok', 'error'],
                    'clima' => ['ultima_ok', 'error'],
                    'calculadoras' => ['ultima_ok', 'error'],
                    'ruc' => ['ultima_ok', 'error'],
                    'dni' => ['ultima_ok', 'error'],
                ],
            ],
            'meta' => [
                'actualizado_en',
            ],
        ]);

    expect($response->json('data.estado'))->toBe(HealthCheck::STATUS_OK);
});

test('health endpoint reporta degradado cuando algún módulo tiene error activo', function () {
    FuenteEstado::query()->where('modulo', 'dolar')->update([
        'ultimo_error' => now(),
        'mensaje_error' => 'API externa timeout',
    ]);

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200);
    expect($response->json('data.estado'))->toBe(HealthCheck::STATUS_DEGRADED)
        ->and($response->json('data.modulos.dolar.error'))->toBe('API externa timeout');
});

test('health endpoint responde 503 cuando la base de datos falla', function () {
    $healthMock = Mockery::mock(HealthCheck::class);
    $healthMock->shouldReceive('run')->once()->andReturn([
        'estado' => HealthCheck::STATUS_ERROR,
        'base_de_datos' => ['ok' => false, 'conexion' => 'sqlite'],
        'cache' => ['ok' => true, 'store' => 'file'],
        'modulos' => [],
    ]);

    $this->app->instance(HealthCheck::class, $healthMock);

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(503)
        ->assertJsonPath('data.estado', HealthCheck::STATUS_ERROR);
});
