<?php

use App\Models\ClienteApi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('comando clientes:crear registra el cliente y muestra la clave una sola vez', function () {
    $this->artisan('clientes:crear "App Android Oficial" --limite=180')
        ->expectsOutputToContain('Cliente API creado exitosamente:')
        ->expectsOutputToContain('App Android Oficial')
        ->expectsOutputToContain('X-App-Key:')
        ->assertSuccessful();

    $this->assertDatabaseHas('clientes_api', [
        'nombre' => 'App Android Oficial',
        'activo' => true,
        'limite_por_minuto' => 180,
    ]);

    $cliente = ClienteApi::where('nombre', 'App Android Oficial')->first();
    expect($cliente)->not->toBeNull()
        ->and(strlen($cliente->clave_hash))->toBe(64); // SHA-256

    // Verifica que en base de datos nunca se guarda el prefijo 'chp_live_' en texto plano
    expect(str_contains($cliente->clave_hash, 'chp_live_'))->toBeFalse();
});

test('comando clientes:crear falla cuando el nombre esta vacio', function () {
    $this->artisan('clientes:crear "   "')
        ->expectsOutputToContain('El nombre del cliente no puede estar vacío.')
        ->assertFailed();
});

test('comando clientes:crear falla cuando el limite es menor o igual a cero', function () {
    $this->artisan('clientes:crear "App Test" --limite=0')
        ->expectsOutputToContain('El límite por minuto debe ser mayor a 0.')
        ->assertFailed();
});
