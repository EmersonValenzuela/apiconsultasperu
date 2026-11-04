<?php

use App\Modules\Feriados\Jobs\SyncFeriadosJob;
use App\Modules\Feriados\Models\Feriado;
use App\Modules\Feriados\Services\FeriadosService;
use Database\Seeders\FeriadosSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(FeriadosSeeder::class);
});

test('GET /api/v1/feriados devuelve feriados del año actual con cabeceras de caché de 24h', function () {
    $response = $this->get('/api/v1/feriados');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=86400, public, stale-while-revalidate=172800')
        ->assertHeader('ETag')
        ->assertJsonStructure([
            'data' => [
                '*' => ['fecha', 'nombre', 'tipo'],
            ],
            'meta' => ['total', 'anio', 'actualizado_en', 'fuente', 'stale'],
        ])
        ->assertJsonPath('meta.anio', 2026);

    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(16);
});

test('GET /api/v1/feriados valida que el año esté en el rango permitido (año actual ± 1)', function () {
    $currentYear = (int) now('America/Lima')->format('Y');

    // Año permitido: año actual - 1
    $this->getJson('/api/v1/feriados?anio=' . ($currentYear - 1))
        ->assertStatus(200);

    // Año permitido: año actual + 1
    $this->getJson('/api/v1/feriados?anio=' . ($currentYear + 1))
        ->assertStatus(200);

    // Año no permitido: año actual - 2
    $this->getJson('/api/v1/feriados?anio=' . ($currentYear - 2))
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');

    // Año no permitido: año actual + 2
    $this->getJson('/api/v1/feriados?anio=' . ($currentYear + 2))
        ->assertStatus(422)
        ->assertJsonPath('error.codigo', 'validacion_fallida');
});

test('GET /api/v1/feriados/proximo calcula exactamente el próximo feriado y los días restantes', function () {
    // Fijamos la fecha actual en 2026-12-01 en zona America/Lima
    Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'America/Lima'));

    $response = $this->get('/api/v1/feriados/proximo');

    // El siguiente feriado después del 2026-12-01 es Inmaculada Concepción (2026-12-08) => 7 días
    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'fecha' => '2026-12-08',
                'nombre' => 'Inmaculada Concepción',
                'tipo' => 'feriado',
                'dias_faltantes' => 7,
            ],
        ]);

    Carbon::setTestNow(); // Restaurar
});

test('GET /api/v1/feriados/proximo devuelve 0 días faltantes si hoy es feriado', function () {
    Carbon::setTestNow(Carbon::parse('2026-12-08 14:00:00', 'America/Lima'));

    $response = $this->get('/api/v1/feriados/proximo');

    $response->assertStatus(200)
        ->assertJsonPath('data.fecha', '2026-12-08')
        ->assertJsonPath('data.dias_faltantes', 0);

    Carbon::setTestNow();
});

test('sincronización con Nager.Date nunca borra ni sobreescribe feriados manuales', function () {
    // Feriado agregado manualmente
    Feriado::query()->create([
        'fecha' => '2026-12-24',
        'nombre' => 'Día No Laborable Compensable',
        'tipo' => 'dia_no_laborable',
        'anio' => 2026,
        'fuente' => 'manual',
    ]);

    $fixture = file_get_contents(base_path('tests/Fixtures/nager_date_pe.json'));

    Http::fake([
        'https://date.nager.at/*' => Http::response($fixture, 200),
    ]);

    $service = app(FeriadosService::class);
    $service->syncHolidays(2026);

    // El feriado manual sigue intacto con fuente manual y su tipo original
    $manual = Feriado::query()->where('fecha', '2026-12-24')->first();
    expect($manual)->not()->toBeNull()
        ->and($manual->nombre)->toBe('Día No Laborable Compensable')
        ->and($manual->tipo)->toBe('dia_no_laborable')
        ->and($manual->fuente)->toBe('manual');
});

test('comando feriados:agregar crea un día no laborable manual', function () {
    $this->artisan('feriados:agregar', [
        '--fecha' => '2026-10-09',
        '--nombre' => 'Día no laborable para el sector público',
        '--tipo' => 'dia_no_laborable',
    ])->assertSuccessful();

    $registrado = Feriado::query()->where('fecha', '2026-10-09')->first();
    expect($registrado)->not()->toBeNull()
        ->and($registrado->tipo)->toBe('dia_no_laborable')
        ->and($registrado->fuente)->toBe('manual');
});

test('SyncFeriadosJob sincroniza los feriados', function () {
    Feriado::query()->delete();

    $fixture = file_get_contents(base_path('tests/Fixtures/nager_date_pe.json'));

    Http::fake([
        'https://date.nager.at/*' => Http::response($fixture, 200),
    ]);

    (new SyncFeriadosJob())->handle(app(FeriadosService::class));

    expect(Feriado::query()->where('fuente', 'nager')->exists())->toBeTrue();
});
