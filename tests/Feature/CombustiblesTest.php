<?php

use Database\Seeders\CombustiblesSeeder;

beforeEach(function () {
    $this->seed(CombustiblesSeeder::class);
});

test('retorna el listado de precios de combustibles con envelope estandar', function () {
    $response = $this->getJson('/api/v1/combustibles');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'region',
                    'tipo',
                    'nombre',
                    'unidad',
                    'simbolo_unidad',
                    'precio_promedio',
                    'precio_minimo',
                    'precio_maximo',
                    'fuente',
                    'vigencia_desde',
                ],
            ],
            'meta' => [
                'actualizado_en',
                'fuente',
                'vigencia',
                'region',
                'aviso',
            ],
        ]);

    $tipos = collect($response->json('data'))->pluck('tipo')->all();
    expect($tipos)->toContain('gasolina_regular')
        ->toContain('gasolina_premium')
        ->toContain('diesel')
        ->toContain('glp_vehicular')
        ->toContain('gnv')
        ->toContain('balon_gas_10kg');
});

test('calcula el llenado de tanque con valores correctos y ahorro estimado', function () {
    $response = $this->postJson('/api/v1/combustibles/calcular-tanque', [
        'tipo' => 'gasolina_regular',
        'cantidad' => 10,
        'tipo_calculo' => 'llenar_tanque',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'tipo',
                'nombre',
                'unidad',
                'simbolo_unidad',
                'tipo_calculo',
                'cantidad_tanque',
                'costo_promedio',
                'costo_minimo',
                'costo_maximo',
                'ahorro_potencial',
            ],
            'meta' => [
                'calculado_en',
                'fuente',
                'referencial',
            ],
        ]);

    $data = $response->json('data');
    expect($data['costo_minimo'])->toBeLessThan($data['costo_promedio'])
        ->and($data['costo_promedio'])->toBeLessThan($data['costo_maximo'])
        ->and($data['ahorro_potencial'])->toBeGreaterThan(0);
});

test('calcula volumen por presupuesto en soles', function () {
    $response = $this->postJson('/api/v1/combustibles/calcular-tanque', [
        'tipo' => 'gasolina_premium',
        'cantidad' => 100,
        'tipo_calculo' => 'presupuesto_soles',
    ]);

    $response->assertOk();
    $data = $response->json('data');
    expect($data['volumen_promedio'])->toBeGreaterThan(0)
        ->and($data['volumen_maximo'])->toBeGreaterThan($data['volumen_minimo'])
        ->and($data['volumen_extra'])->toBeGreaterThan(0);
});

test('falla con 422 si los parametros son invalidos', function () {
    $response = $this->postJson('/api/v1/combustibles/calcular-tanque', [
        'tipo' => 'combustible_inexistente',
        'cantidad' => -5,
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'error' => [
                'codigo',
                'mensaje',
                'detalles',
            ],
        ]);
});
