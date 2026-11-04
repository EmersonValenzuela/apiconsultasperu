<?php

use App\Models\FuenteEstado;
use App\Modules\Sismos\Jobs\SyncSismosJob;
use App\Modules\Sismos\Models\Sismo;
use App\Modules\Sismos\Services\SismosService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

test('sincronizar sismos desde USGS es idempotente y no duplica eventos', function () {
    $fixture = file_get_contents(base_path('tests/Fixtures/usgs_earthquakes_peru.json'));

    Http::fake([
        'https://earthquake.usgs.gov/*' => Http::response($fixture, 200),
    ]);

    $service = app(SismosService::class);

    // Primera sincronización: debe insertar 2 sismos de Perú (descarta el de Chile)
    $countFirst = $service->syncSismos();
    expect($countFirst)->toBe(2)
        ->and(Sismo::count())->toBe(2);

    // Segunda sincronización (mismos datos): no debe duplicar registros
    $countSecond = $service->syncSismos();
    expect($countSecond)->toBe(2)
        ->and(Sismo::count())->toBe(2);

    // Verifica que el sismo de Chile fue descartado
    expect(Sismo::query()->where('id_externo', 'us7000chile1')->exists())->toBeFalse();

    // Verifica que fuentes_estado se actualizó
    $estado = FuenteEstado::query()->find('sismos');
    expect($estado?->ultima_ok)->not()->toBeNull()
        ->and($estado?->intentos_fallidos)->toBe(0);
});

test('SyncSismosJob ejecuta la sincronización correctamente', function () {
    $fixture = file_get_contents(base_path('tests/Fixtures/usgs_earthquakes_peru.json'));

    Http::fake([
        'https://earthquake.usgs.gov/*' => Http::response($fixture, 200),
    ]);

    (new SyncSismosJob())->handle(app(SismosService::class));

    expect(Sismo::count())->toBe(2);
});

test('GET /api/v1/sismos lista eventos ordenados del más reciente al más antiguo con cabeceras de caché', function () {
    Sismo::query()->create([
        'id_externo' => 'evento_antiguo',
        'magnitud' => 4.2,
        'lugar' => '20 km N de Chimbote',
        'profundidad_km' => 45.0,
        'latitud' => -9.0,
        'longitud' => -78.5,
        'ocurrido_en' => Carbon::parse('2026-11-02 10:00:00', 'America/Lima'),
        'fuente' => 'USGS',
    ]);

    Sismo::query()->create([
        'id_externo' => 'evento_reciente',
        'magnitud' => 5.5,
        'lugar' => '30 km S de Pisco',
        'profundidad_km' => 20.0,
        'latitud' => -13.7,
        'longitud' => -76.2,
        'ocurrido_en' => Carbon::parse('2026-11-04 15:00:00', 'America/Lima'),
        'fuente' => 'USGS',
    ]);

    $response = $this->get('/api/v1/sismos');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=300, public, stale-while-revalidate=600')
        ->assertHeader('ETag')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'magnitud', 'lugar', 'profundidad_km', 'latitud', 'longitud', 'ocurrido_en', 'fuente', 'url'],
            ],
            'meta' => ['total', 'actualizado_en', 'fuente', 'stale'],
        ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2)
        ->and($data[0]['lugar'])->toBe('30 km S de Pisco') // El más reciente primero
        ->and($data[1]['lugar'])->toBe('20 km N de Chimbote');
});

test('GET /api/v1/sismos filtra por magnitud mínima', function () {
    Sismo::query()->create([
        'id_externo' => 'sismo_leve',
        'magnitud' => 3.5,
        'lugar' => 'Lima',
        'latitud' => -12.0,
        'longitud' => -77.0,
        'ocurrido_en' => now(),
        'fuente' => 'USGS',
    ]);

    Sismo::query()->create([
        'id_externo' => 'sismo_fuerte',
        'magnitud' => 6.0,
        'lugar' => 'Arequipa',
        'latitud' => -16.4,
        'longitud' => -71.5,
        'ocurrido_en' => now(),
        'fuente' => 'USGS',
    ]);

    $response = $this->getJson('/api/v1/sismos?min_magnitud=5.0');

    $response->assertStatus(200)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.magnitud', 6.0);
});

test('GET /api/v1/sismos valida filtros inválidos con 422', function () {
    // Días fuera de rango (> 30)
    $this->getJson('/api/v1/sismos?dias=40')
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');

    // Límite fuera de rango (> 100)
    $this->getJson('/api/v1/sismos?limite=150')
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');

    // Magnitud negativa
    $this->getJson('/api/v1/sismos?min_magnitud=-1')
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');
});

test('GET /api/v1/sismos devuelve stale = true si la fuente tiene error activo registrado', function () {
    FuenteEstado::query()->updateOrCreate(
        ['modulo' => 'sismos'],
        [
            'ultimo_error' => now(),
            'mensaje_error' => 'USGS timeout',
        ]
    );

    $response = $this->get('/api/v1/sismos');

    $response->assertStatus(200)
        ->assertJsonPath('meta.stale', true);
});

test('GET /api/v1/sismos/{id} devuelve el detalle del sismo', function () {
    $sismo = Sismo::query()->create([
        'id_externo' => 'evento_detalle',
        'magnitud' => 5.0,
        'lugar' => '25 km E de Trujillo',
        'profundidad_km' => 60.5,
        'latitud' => -8.1,
        'longitud' => -79.0,
        'ocurrido_en' => now(),
        'fuente' => 'USGS',
        'url' => 'https://earthquake.usgs.gov/events/1',
    ]);

    $response = $this->get("/api/v1/sismos/{$sismo->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $sismo->id,
                'magnitud' => 5.0,
                'lugar' => '25 km E de Trujillo',
                'profundidad_km' => 60.5,
                'fuente' => 'USGS',
            ],
            'meta' => [
                'fuente' => 'USGS',
                'stale' => false,
            ],
        ]);
});

test('GET /api/v1/sismos/{id} devuelve 404 si el sismo no existe', function () {
    $response = $this->get('/api/v1/sismos/99999');

    $response->assertStatus(404)
        ->assertJson([
            'error' => [
                'codigo' => 'no_encontrado',
            ],
        ]);
});

test('comando sismos:purgar elimina eventos con más de 1 año', function () {
    // Sismo de hace 400 días (debe ser eliminado)
    Sismo::query()->create([
        'id_externo' => 'sismo_viejo',
        'magnitud' => 4.0,
        'lugar' => 'Cusco',
        'latitud' => -13.5,
        'longitud' => -71.9,
        'ocurrido_en' => now()->subDays(400),
        'fuente' => 'USGS',
    ]);

    // Sismo reciente (debe mantenerse)
    Sismo::query()->create([
        'id_externo' => 'sismo_nuevo',
        'magnitud' => 4.0,
        'lugar' => 'Cusco',
        'latitud' => -13.5,
        'longitud' => -71.9,
        'ocurrido_en' => now()->subDays(10),
        'fuente' => 'USGS',
    ]);

    $this->artisan('sismos:purgar')
        ->assertSuccessful();

    expect(Sismo::query()->where('id_externo', 'sismo_viejo')->exists())->toBeFalse()
        ->and(Sismo::query()->where('id_externo', 'sismo_nuevo')->exists())->toBeTrue();
});
