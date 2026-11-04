<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('GET /api/v1/monedas/convertir realiza conversión correctamente', function () {
    $fixture = file_get_contents(base_path('tests/Fixtures/open_er_api_usd.json'));

    Http::fake([
        'https://open.er-api.com/*' => Http::response($fixture, 200),
    ]);

    $response = $this->get('/api/v1/monedas/convertir?de=USD&a=PEN&monto=100');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=1800, public, stale-while-revalidate=3600')
        ->assertHeader('ETag')
        ->assertJson([
            'data' => [
                'de' => 'USD',
                'a' => 'PEN',
                'monto' => 100,
                'resultado' => 375.4,
                'tasa' => 3.754,
            ],
            'meta' => [
                'fuente' => 'open.er-api.com',
                'stale' => false,
            ],
        ]);
});

test('GET /api/v1/monedas/convertir valida monedas no soportadas con 422', function () {
    $response = $this->getJson('/api/v1/monedas/convertir?de=XYZ&a=PEN&monto=100');

    $response->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida')
        ->assertJsonStructure(['error' => ['detalles' => ['de']]]);
});

test('GET /api/v1/monedas/convertir valida montos negativos o cero con 422', function () {
    $response = $this->getJson('/api/v1/monedas/convertir?de=USD&a=PEN&monto=-10');

    $response->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida')
        ->assertJsonStructure(['error' => ['detalles' => ['monto']]]);
});
