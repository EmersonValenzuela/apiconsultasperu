<?php

use App\Models\ConsultaLog;
use App\Modules\Ruc\Contracts\RucProvider;
use App\Modules\Ruc\Services\RucService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('rechaza ruc con formato o digito verificador invalido con 422 sin llamar a la api externa', function () {
    Http::fake();

    $response = $this->getJson('/api/v1/ruc/20100070979'); // Dígito inválido

    $response->assertStatus(422)
        ->assertJson([
            'error' => [
                'codigo' => 'validacion_fallida',
                'mensaje' => 'El campo ruc debe tener 11 dígitos y un dígito verificador válido.',
            ],
        ]);

    Http::assertNothingSent();

    // Verifica que se registró en auditoría con hash, nunca en texto plano
    $this->assertDatabaseCount('consultas_log', 1);
    $log = ConsultaLog::first();
    expect($log->tipo)->toBe('ruc')
        ->and($log->resultado)->toBe('error')
        ->and($log->documento_hash)->not->toBe('20100070979')
        ->and(strlen($log->documento_hash))->toBe(64);
});

test('consulta ruc exitosa retorna envelope estandar y almacena en cache por 7 dias', function () {
    $baseUrl = config('services.ruc.peru_consult.base_url');

    Http::fake([
        "{$baseUrl}/api/v1/ruc/20100070970*" => Http::response([
            'success' => true,
            'data' => [
                'ruc' => '20100070970',
                'razon_social' => 'SUPERINTENDENCIA NACIONAL DE ADUANAS Y DE ADMINISTRACION TRIBUTARIA',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
                'direccion' => 'AV. GARCILASO DE LA VEGA NRO. 1472',
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'LIMA',
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/v1/ruc/20100070970');

    $response->assertStatus(200)
        ->assertHeader('Cache-Control')
        ->assertHeader('ETag')
        ->assertJson([
            'data' => [
                'ruc' => '20100070970',
                'razon_social' => 'SUPERINTENDENCIA NACIONAL DE ADUANAS Y DE ADMINISTRACION TRIBUTARIA',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'LIMA',
            ],
            'meta' => [
                'fuente' => 'peru_consult',
                'stale' => false,
            ],
        ]);

    Http::assertSentCount(1);

    // Segunda llamada debe salir de la caché sin llamar a la API externa
    $secondResponse = $this->getJson('/api/v1/ruc/20100070970');
    $secondResponse->assertStatus(200);
    Http::assertSentCount(1);

    // ETag 304 Not Modified
    $etag = $response->headers->get('ETag');
    $this->getJson('/api/v1/ruc/20100070970', ['If-None-Match' => $etag])
        ->assertStatus(304);

    // Verifica auditoría protegida
    $this->assertDatabaseHas('consultas_log', [
        'tipo' => 'ruc',
        'resultado' => 'ok',
    ]);
    expect(ConsultaLog::where('documento_hash', 'like', '%20100070970%')->exists())->toBeFalse();
});

test('retorna 404 cuando el ruc no existe en sunat', function () {
    $baseUrl = config('services.ruc.peru_consult.base_url');

    Http::fake([
        "{$baseUrl}/api/v1/ruc/20100070970*" => Http::response([
            'success' => false,
            'message' => 'RUC no encontrado',
        ], 404),
    ]);

    $response = $this->getJson('/api/v1/ruc/20100070970');

    $response->assertStatus(404)
        ->assertJson([
            'error' => [
                'codigo' => 'no_encontrado',
            ],
        ]);

    $this->assertDatabaseHas('consultas_log', [
        'tipo' => 'ruc',
        'resultado' => ConsultaLog::RESULTADO_NOT_FOUND,
    ]);
});

test('retorna 503 cuando todas las fuentes externas fallan y actualiza fuentes_estado', function () {
    $baseUrl = config('services.ruc.peru_consult.base_url');

    Http::fake([
        "{$baseUrl}/api/v1/ruc/20100070970*" => Http::response(null, 500),
    ]);

    $response = $this->getJson('/api/v1/ruc/20100070970');

    $response->assertStatus(503)
        ->assertJson([
            'error' => [
                'codigo' => 'fuente_no_disponible',
                'mensaje' => 'El servicio de consulta RUC no está disponible en este momento.',
            ],
        ]);

    $this->assertDatabaseHas('fuentes_estado', [
        'modulo' => 'ruc',
    ]);
});

test('aplica failover entre drivers cuando el primer driver falla', function () {
    // Configuramos driver mock que falla y segundo driver que responde
    $failingDriver = new class () implements RucProvider {
        public function name(): string
        {
            return 'mock_failing';
        }

        public function find(string $ruc): ?array
        {
            throw new \RuntimeException('Provider error');
        }
    };

    $successDriver = new class () implements RucProvider {
        public function name(): string
        {
            return 'mock_backup';
        }

        public function find(string $ruc): ?array
        {
            return [
                'ruc' => $ruc,
                'razon_social' => 'BACKUP EMPRESA S.A.C.',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
                'direccion' => 'CALLE BACKUP 123',
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'SAN ISIDRO',
                'fuente' => 'mock_backup',
            ];
        }
    };

    config(['services.ruc.drivers' => 'mock_failing,mock_backup']);

    $service = app(RucService::class);
    $service->setDriver('mock_failing', $failingDriver);
    $service->setDriver('mock_backup', $successDriver);
    $this->app->instance(RucService::class, $service);

    $response = $this->getJson('/api/v1/ruc/20100070970');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'razon_social' => 'BACKUP EMPRESA S.A.C.',
            ],
            'meta' => [
                'fuente' => 'mock_backup',
            ],
        ]);
});

test('comando consultas:purgar elimina logs mayores a 90 dias', function () {
    // Log antiguo (> 90 días)
    ConsultaLog::query()->create([
        'tipo' => 'ruc',
        'documento_hash' => hash_hmac('sha256', '20100070970', 'key'),
        'ip_hash' => hash_hmac('sha256', '127.0.0.1', 'key'),
        'resultado' => 'ok',
        'creado_en' => now('America/Lima')->subDays(95),
    ]);

    // Log reciente (< 90 días)
    ConsultaLog::query()->create([
        'tipo' => 'ruc',
        'documento_hash' => hash_hmac('sha256', '20100047218', 'key'),
        'ip_hash' => hash_hmac('sha256', '127.0.0.1', 'key'),
        'resultado' => 'ok',
        'creado_en' => now('America/Lima')->subDays(10),
    ]);

    $this->assertDatabaseCount('consultas_log', 2);

    $this->artisan('consultas:purgar --days=90')
        ->expectsOutputToContain('Se eliminaron 1 registros antiguos de auditoría.')
        ->assertSuccessful();

    $this->assertDatabaseCount('consultas_log', 1);
});

test('aplica rate limit de 20 peticiones por minuto por ip', function () {
    $baseUrl = config('services.ruc.peru_consult.base_url');
    Http::fake([
        "{$baseUrl}/api/v1/ruc/20100070970*" => Http::response([
            'ruc' => '20100070970',
            'razon_social' => 'EMPRESA TEST',
            'estado' => 'ACTIVO',
            'condicion' => 'HABIDO',
            'direccion' => null,
            'departamento' => null,
            'provincia' => null,
            'distrito' => null,
        ], 200),
    ]);

    for ($i = 0; $i < 20; $i++) {
        $response = $this->getJson('/api/v1/ruc/20100070970');
        $response->assertStatus(200);
    }

    $response = $this->getJson('/api/v1/ruc/20100070970');
    $response->assertStatus(429)
        ->assertJson([
            'error' => [
                'codigo' => 'limite_excedido',
            ],
        ]);
});
