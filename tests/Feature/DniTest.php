<?php

use App\Models\ConsultaLog;
use App\Modules\Dni\Contracts\DniProvider;
use App\Modules\Dni\Services\DniService;
use App\Modules\Dni\Services\TurnstileVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('POST /api/v1/dni/validar verifica formato sin consultar fuentes externas ni captcha', function () {
    Http::fake();

    $response = $this->postJson('/api/v1/dni/validar', [
        'dni' => '72345678',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'dni' => '72345678',
                'valido' => true,
                'formato' => 'DNI estándar (8 dígitos)',
            ],
        ]);

    Http::assertNothingSent();
});

test('POST /api/v1/dni/validar rechaza dnis con formato incorrecto con 422', function () {
    $response = $this->postJson('/api/v1/dni/validar', [
        'dni' => '1234',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'error' => [
                'codigo' => 'validacion_fallida',
            ],
        ]);
});

test('POST /api/v1/dni/consulta responde 404 cuando DNI_ENABLED es false por defecto', function () {
    config(['services.dni.enabled' => false]);

    $response = $this->postJson('/api/v1/dni/consulta', [
        'dni' => '72345678',
        'captcha_token' => 'dummy_token',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'error' => [
                'codigo' => 'no_encontrado',
            ],
        ]);
});

test('POST /api/v1/dni/consulta rechaza peticion cuando el captcha Turnstile es invalido', function () {
    config(['services.dni.enabled' => true]);
    config(['services.dni.turnstile.secret_key' => 'fake_secret_key']);
    config(['services.dni.turnstile.bypass_in_testing' => false]);

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ], 200),
    ]);

    $response = $this->postJson('/api/v1/dni/consulta', [
        'dni' => '72345678',
        'captcha_token' => 'invalid_token',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'error' => [
                'codigo' => 'validacion_fallida',
                'mensaje' => 'La verificación del captcha de seguridad falló o ha expirado.',
            ],
        ]);

    // Debe registrar el error en auditoría anonimizada
    $this->assertDatabaseHas('consultas_log', [
        'tipo' => 'dni',
        'resultado' => 'error',
    ]);
});

test('POST /api/v1/dni/consulta exitosa devuelve datos minimizados, enmascaramiento y cabeceras no-store', function () {
    config(['services.dni.enabled' => true]);
    config(['services.dni.turnstile.bypass_in_testing' => true]);

    $mockProvider = new class () implements DniProvider {
        public function name(): string
        {
            return 'peru_consult';
        }

        public function find(string $dni): ?array
        {
            return [
                'dni' => $dni,
                'nombres' => 'JUAN CARLOS',
                'apellido_paterno' => 'PEREZ',
                'apellido_materno' => 'RODRIGUEZ',
                'fuente' => 'peru_consult',
            ];
        }
    };

    $service = new DniService(new TurnstileVerifier(), $mockProvider);
    $this->app->instance(DniService::class, $service);

    $response = $this->postJson('/api/v1/dni/consulta', [
        'dni' => '72345678',
        'captcha_token' => 'valid_token',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'dni_enmascarado' => '7234****',
                'nombres' => 'JUAN CARLOS',
                'apellido_paterno' => 'PEREZ',
                'apellido_materno' => 'RODRIGUEZ',
            ],
            'meta' => [
                'fuente' => 'peru_consult',
                'stale' => false,
            ],
        ]);

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('private');

    // La respuesta NO debe exponer campos no solicitados
    $data = $response->json('data');
    expect(array_key_exists('dni', $data))->toBeFalse()
        ->and(array_key_exists('direccion', $data))->toBeFalse()
        ->and(array_key_exists('foto', $data))->toBeFalse();

    // Caché volátil en store de caché con clave basada en hash SHA-256 (docs/07)
    $hash = hash('sha256', '72345678');
    expect(Cache::has("dni:hash:{$hash}"))->toBeTrue();
});

test('test de privacidad estricto: ningun DNI ni nombre completo se almacena en base de datos', function () {
    config(['services.dni.enabled' => true]);
    config(['services.dni.turnstile.bypass_in_testing' => true]);

    $mockProvider = new class () implements DniProvider {
        public function name(): string
        {
            return 'peru_consult';
        }

        public function find(string $dni): ?array
        {
            return [
                'dni' => $dni,
                'nombres' => 'MARIA ELENA',
                'apellido_paterno' => 'QUISPE',
                'apellido_materno' => 'MAMANI',
                'fuente' => 'peru_consult',
            ];
        }
    };

    $service = new DniService(new TurnstileVerifier(), $mockProvider);
    $this->app->instance(DniService::class, $service);

    $dniTest = '78912345';
    $this->postJson('/api/v1/dni/consulta', [
        'dni' => $dniTest,
        'captcha_token' => 'valid_token',
    ])->assertStatus(200);

    // 1. En la base de datos se registró la auditoría
    $this->assertDatabaseCount('consultas_log', 1);
    $log = ConsultaLog::first();

    // 2. El documento_hash es un HMAC-SHA256 de 64 caracteres, NUNCA el DNI
    expect($log->tipo)->toBe('dni')
        ->and($log->resultado)->toBe('ok')
        ->and(strlen($log->documento_hash))->toBe(64)
        ->and($log->documento_hash)->not->toContain($dniTest);

    // 3. Verificación exhaustiva: el DNI ni el nombre del ciudadano existen en ninguna tabla
    $dniCount = ConsultaLog::where('documento_hash', 'like', "%{$dniTest}%")->count();
    expect($dniCount)->toBe(0);

    $nameCount = ConsultaLog::where('documento_hash', 'like', '%QUISPE%')->count();
    expect($nameCount)->toBe(0);
});

test('aplica rate limit estricto de 5 peticiones por minuto para consulta DNI', function () {
    config(['services.dni.enabled' => true]);
    config(['services.dni.turnstile.bypass_in_testing' => true]);

    $mockProvider = new class () implements DniProvider {
        public function name(): string
        {
            return 'peru_consult';
        }

        public function find(string $dni): ?array
        {
            return [
                'dni' => $dni,
                'nombres' => 'CARLOS',
                'apellido_paterno' => 'ALVAREZ',
                'apellido_materno' => 'SOTO',
                'fuente' => 'peru_consult',
            ];
        }
    };

    $service = new DniService(new TurnstileVerifier(), $mockProvider);
    $this->app->instance(DniService::class, $service);

    // 5 peticiones exitosas
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/v1/dni/consulta', [
            'dni' => '72345678',
            'captcha_token' => 'token',
        ]);
        $response->assertStatus(200);
    }

    // 6ta petición debe retornar 429 límite excedido
    $response = $this->postJson('/api/v1/dni/consulta', [
        'dni' => '72345678',
        'captcha_token' => 'token',
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'error' => [
                'codigo' => 'limite_excedido',
            ],
        ]);
});
