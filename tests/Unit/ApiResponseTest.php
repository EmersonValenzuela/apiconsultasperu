<?php

use App\Support\ApiResponse;

test('ApiResponse::success devuelve json con envelope data y meta opcional', function () {
    $response = ApiResponse::success(['saludo' => 'hola'], ['total' => 1]);

    expect($response->status())->toBe(200);
    $data = $response->getData(true);

    expect($data)->toBe([
        'data' => ['saludo' => 'hola'],
        'meta' => ['total' => 1],
    ]);
});

test('ApiResponse::fromSource incluye actualizado_en, fuente y stale en meta', function () {
    $now = new DateTimeImmutable('2026-11-04T12:00:00-05:00');
    $response = ApiResponse::fromSource(
        ['precio' => 3.75],
        $now,
        'open.er-api.com',
        stale: true,
        extraMeta: ['tipo' => 'referencial']
    );

    $data = $response->getData(true);

    expect($data['meta'])->toBe([
        'actualizado_en' => '2026-11-04T12:00:00-05:00',
        'fuente' => 'open.er-api.com',
        'stale' => true,
        'tipo' => 'referencial',
    ]);
});

test('ApiResponse::error devuelve estructura con codigo, mensaje y opcional detalles', function () {
    $response = ApiResponse::error(
        ApiResponse::ERROR_VALIDATION,
        'DNI inválido',
        422,
        ['dni' => ['Debe tener 8 dígitos']]
    );

    expect($response->status())->toBe(422);
    $data = $response->getData(true);

    expect($data)->toBe([
        'error' => [
            'codigo' => 'validacion_fallida',
            'mensaje' => 'DNI inválido',
            'detalles' => [
                'dni' => ['Debe tener 8 dígitos'],
            ],
        ],
    ]);
});
