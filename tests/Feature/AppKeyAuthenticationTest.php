<?php

use App\Models\ClienteApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('peticion sin cabecera X-App-Key funciona en modo publico sin requerir autenticacion', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'estado',
                'base_de_datos',
                'cache',
                'modulos',
            ],
            'meta',
        ]);
});

test('peticion con cabecera X-App-Key vacia retorna 401 no_autenticado', function () {
    $response = $this->getJson('/api/v1/health', [
        'X-App-Key' => '   ',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => [
                'codigo' => 'no_autenticado',
                'mensaje' => 'La cabecera X-App-Key no puede estar vacía.',
            ],
        ]);
});

test('peticion con clave inexistente retorna 401 no_autenticado', function () {
    $response = $this->getJson('/api/v1/health', [
        'X-App-Key' => 'chp_live_clave_que_no_existe_1234567890',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => [
                'codigo' => 'no_autenticado',
                'mensaje' => 'La clave de aplicación provista no es válida o está inactiva.',
            ],
        ]);
});

test('peticion con clave de cliente inactivo retorna 401 no_autenticado', function () {
    $rawKey = 'chp_live_cliente_inactivo_token_seguro';

    ClienteApi::query()->create([
        'nombre' => 'App Desactivada',
        'clave_hash' => hash('sha256', $rawKey),
        'activo' => false,
        'limite_por_minuto' => 100,
    ]);

    $response = $this->getJson('/api/v1/health', [
        'X-App-Key' => $rawKey,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => [
                'codigo' => 'no_autenticado',
                'mensaje' => 'La clave de aplicación provista no es válida o está inactiva.',
            ],
        ]);
});

test('peticion con clave valida permite acceso y aplica cuota configurada de cliente', function () {
    $rawKey = 'chp_live_cliente_activo_flutter_app';

    $cliente = ClienteApi::query()->create([
        'nombre' => 'App Móvil Oficial',
        'clave_hash' => hash('sha256', $rawKey),
        'activo' => true,
        'limite_por_minuto' => 3, // Límite bajo para probar rápidamente el throttle
    ]);

    // 3 peticiones deben responder 200
    for ($i = 0; $i < 3; $i++) {
        $response = $this->getJson('/api/v1/health', [
            'X-App-Key' => $rawKey,
        ]);
        $response->assertStatus(200);
    }

    // 4ta petición excede el límite del cliente y debe retornar 429
    $response = $this->getJson('/api/v1/health', [
        'X-App-Key' => $rawKey,
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'error' => [
                'codigo' => 'limite_excedido',
            ],
        ]);
});
