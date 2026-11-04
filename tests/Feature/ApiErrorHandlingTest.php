<?php

use App\Support\ApiResponse;
use App\Support\Exceptions\SourceUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/api/test-validation', function (Request $request) {
        $request->validate([
            'campo_requerido' => ['required', 'string'],
        ]);

        return ApiResponse::success(['ok' => true]);
    });

    Route::get('/api/test-source-unavailable', function () {
        throw new SourceUnavailableException('Servicio externo caído');
    });

    Route::get('/api/test-server-error', function () {
        throw new Exception('Error de servidor no controlado');
    });
});

test('ruta inexistente en /api devuelve 404 en formato json estándar', function () {
    $response = $this->get('/api/v1/ruta-inexistente');

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertJson([
            'error' => [
                'codigo' => ApiResponse::ERROR_NOT_FOUND,
                'mensaje' => 'El recurso solicitado no existe.',
            ],
        ]);
});

test('error de validación devuelve 422 con formato estándar y detalles por campo', function () {
    $response = $this->postJson('/api/test-validation', []);

    $response->assertStatus(422)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertJsonStructure([
            'error' => [
                'codigo',
                'mensaje',
                'detalles' => [
                    'campo_requerido',
                ],
            ],
        ])
        ->assertJsonPath('error.codigo', ApiResponse::ERROR_VALIDATION);
});

test('excepción de fuente externa no disponible devuelve 503 con código fuente_no_disponible', function () {
    $response = $this->getJson('/api/test-source-unavailable');

    $response->assertStatus(503)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertJson([
            'error' => [
                'codigo' => ApiResponse::ERROR_SOURCE_UNAVAILABLE,
                'mensaje' => 'Servicio externo caído',
            ],
        ]);
});

test('error no controlado con APP_DEBUG=false nunca expone traza', function () {
    config(['app.debug' => false]);

    $response = $this->getJson('/api/test-server-error');

    $response->assertStatus(500)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertJson([
            'error' => [
                'codigo' => ApiResponse::ERROR_INTERNAL,
                'mensaje' => 'Ocurrió un error interno. Intenta de nuevo más tarde.',
            ],
        ]);

    expect($response->json('error.debug'))->toBeNull();
});

test('solicitud sin cabecera accept recibe json y nunca html', function () {
    $response = $this->call('GET', '/api/v1/ruta-que-no-existe', [], [], [], [
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
    ]);

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8');
});
