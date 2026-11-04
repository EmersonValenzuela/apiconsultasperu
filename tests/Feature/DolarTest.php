<?php

use App\Models\FuenteEstado;
use App\Modules\Dolar\Models\CotizacionMoneda;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('GET /api/v1/dolar devuelve cotización actual con envelope estándar y cabeceras de caché', function () {
    $fixture = file_get_contents(base_path('tests/Fixtures/open_er_api_usd.json'));

    Http::fake([
        'https://open.er-api.com/*' => Http::response($fixture, 200),
    ]);

    $response = $this->get('/api/v1/dolar');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=1800, public, stale-while-revalidate=3600')
        ->assertHeader('ETag')
        ->assertJson([
            'data' => [
                'moneda' => 'USD',
                'compra' => 3.754,
                'venta' => 3.754,
                'fecha' => '2026-11-04',
            ],
            'meta' => [
                'fuente' => 'open.er-api.com',
                'stale' => false,
                'tipo' => 'referencial',
            ],
        ]);

    expect($response->json('meta.actualizado_en'))->not()->toBeNull();

    // Verifica que se guardó en la base de datos
    expect(CotizacionMoneda::query()->where('moneda', 'USD')->exists())->toBeTrue();

    // Verifica que fuentes_estado se actualizó
    $estado = FuenteEstado::query()->where('modulo', 'dolar')->first();
    expect($estado?->ultima_ok)->not()->toBeNull()
        ->and($estado?->intentos_fallidos)->toBe(0);
});

test('GET /api/v1/dolar devuelve 304 Not Modified con ETag coincidente', function () {
    $fixture = file_get_contents(base_path('tests/Fixtures/open_er_api_usd.json'));

    Http::fake([
        'https://open.er-api.com/*' => Http::response($fixture, 200),
    ]);

    $firstResponse = $this->get('/api/v1/dolar');
    $firstResponse->assertStatus(200);

    $etag = $firstResponse->headers->get('ETag');

    $secondResponse = $this->withHeaders(['If-None-Match' => $etag])
        ->get('/api/v1/dolar');

    $secondResponse->assertStatus(304);
});

test('GET /api/v1/dolar devuelve stale = true si la API externa falla pero existe dato en BD', function () {
    // Previamente guardamos un dato histórico
    CotizacionMoneda::query()->create([
        'moneda' => 'USD',
        'compra' => 3.7200,
        'venta' => 3.7200,
        'tipo' => 'referencial',
        'fuente' => 'open.er-api.com',
        'fecha' => '2026-11-03',
        'obtenido_en' => Carbon::parse('2026-11-03T08:00:00-05:00'),
    ]);

    // La API externa falla
    Http::fake([
        'https://open.er-api.com/*' => Http::response(null, 500),
    ]);

    $response = $this->get('/api/v1/dolar');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'moneda' => 'USD',
                'compra' => 3.72,
                'venta' => 3.72,
                'fecha' => '2026-11-03',
            ],
            'meta' => [
                'fuente' => 'open.er-api.com',
                'stale' => true,
                'tipo' => 'referencial',
            ],
        ]);
});

test('GET /api/v1/dolar devuelve 503 si la API externa falla y no hay dato previo en BD', function () {
    Http::fake([
        'https://open.er-api.com/*' => Http::response(null, 500),
    ]);

    $response = $this->get('/api/v1/dolar');

    $response->assertStatus(503)
        ->assertJson([
            'error' => [
                'codigo' => 'fuente_no_disponible',
            ],
        ]);
});

test('GET /api/v1/dolar/historico devuelve serie en el rango y valida parámetros', function () {
    CotizacionMoneda::query()->create([
        'moneda' => 'USD',
        'compra' => 3.71,
        'venta' => 3.71,
        'tipo' => 'referencial',
        'fuente' => 'open.er-api.com',
        'fecha' => '2026-11-01',
        'obtenido_en' => now(),
    ]);

    CotizacionMoneda::query()->create([
        'moneda' => 'USD',
        'compra' => 3.73,
        'venta' => 3.73,
        'tipo' => 'referencial',
        'fuente' => 'open.er-api.com',
        'fecha' => '2026-11-02',
        'obtenido_en' => now(),
    ]);

    $response = $this->get('/api/v1/dolar/historico?desde=2026-11-01&hasta=2026-11-02');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['fecha', 'compra', 'venta'],
            ],
            'meta' => ['total', 'actualizado_en', 'fuente', 'stale'],
        ])
        ->assertJsonPath('meta.total', 2);
});

test('GET /api/v1/dolar/historico rechaza rango mayor a 366 días con 422', function () {
    $response = $this->getJson('/api/v1/dolar/historico?desde=2024-01-01&hasta=2025-02-01');

    $response->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');
});

test('GET /api/v1/dolar/historico rechaza si hasta es anterior a desde con 422', function () {
    $response = $this->getJson('/api/v1/dolar/historico?desde=2026-11-05&hasta=2026-11-01');

    $response->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');
});
