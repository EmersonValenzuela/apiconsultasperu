<?php

use App\Models\ClienteApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('genera clave aleatoria con prefijo chp_live_ y alta entropia', function () {
    $key1 = ClienteApi::generateRawKey();
    $key2 = ClienteApi::generateRawKey();

    expect($key1)->toStartWith('chp_live_')
        ->and(strlen($key1))->toBeGreaterThanOrEqual(48)
        ->and($key1)->not->toBe($key2);
});

test('calcula hash sha256 consistente', function () {
    $rawKey = 'chp_live_1234567890abcdef';
    $expected = hash('sha256', $rawKey);

    expect(ClienteApi::hashKey($rawKey))->toBe($expected)
        ->and(strlen(ClienteApi::hashKey($rawKey)))->toBe(64);
});

test('findActiveByRawKey recupera y cachea el cliente activo', function () {
    $rawKey = ClienteApi::generateRawKey();

    $cliente = ClienteApi::query()->create([
        'nombre' => 'App Cache Test',
        'clave_hash' => ClienteApi::hashKey($rawKey),
        'activo' => true,
        'limite_por_minuto' => 120,
    ]);

    $found = ClienteApi::findActiveByRawKey($rawKey);
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($cliente->id);

    // Debe estar en caché
    $cacheKey = 'cliente_api:hash:'.ClienteApi::hashKey($rawKey);
    expect(Cache::has($cacheKey))->toBeTrue();

    // Invalidar caché
    $cliente->invalidateCache();
    expect(Cache::has($cacheKey))->toBeFalse();
});
