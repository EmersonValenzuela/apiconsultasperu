<?php

namespace App\Providers;

use App\Modules\Dolar\Clients\OpenErApiClient;
use App\Modules\Dolar\Contracts\ExchangeRateProvider;
use App\Modules\Feriados\Clients\NagerDateClient;
use App\Modules\Feriados\Contracts\HolidayProvider;
use App\Modules\Sismos\Clients\UsgsEarthquakeClient;
use App\Modules\Sismos\Contracts\EarthquakeProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExchangeRateProvider::class, function () {
            $driver = config('services.dolar.driver', 'open_er_api');

            return match ($driver) {
                'open_er_api' => new OpenErApiClient(
                    baseUrl: config('services.dolar.open_er_api.base_url'),
                    timeout: (int) config('services.dolar.open_er_api.timeout', 5),
                    retryTimes: (int) config('services.dolar.open_er_api.retry_times', 2),
                    retrySleep: (int) config('services.dolar.open_er_api.retry_sleep', 200),
                ),
                default => throw new \InvalidArgumentException("Driver de dólar no soportado: {$driver}"),
            };
        });

        $this->app->bind(EarthquakeProvider::class, function () {
            $driver = config('services.sismos.driver', 'usgs');

            return match ($driver) {
                'usgs' => new UsgsEarthquakeClient(
                    baseUrl: config('services.sismos.usgs.base_url'),
                    timeout: (int) config('services.sismos.usgs.timeout', 5),
                    retryTimes: (int) config('services.sismos.usgs.retry_times', 2),
                    retrySleep: (int) config('services.sismos.usgs.retry_sleep', 200),
                    minLatitude: (float) config('services.sismos.usgs.minlatitude', -19.0),
                    maxLatitude: (float) config('services.sismos.usgs.maxlatitude', 0.5),
                    minLongitude: (float) config('services.sismos.usgs.minlongitude', -82.0),
                    maxLongitude: (float) config('services.sismos.usgs.maxlongitude', -68.0),
                ),
                default => throw new \InvalidArgumentException("Driver de sismos no soportado: {$driver}"),
            };
        });

        $this->app->bind(HolidayProvider::class, function () {
            $driver = config('services.feriados.driver', 'nager');

            return match ($driver) {
                'nager' => new NagerDateClient(
                    baseUrl: config('services.feriados.nager.base_url'),
                    timeout: (int) config('services.feriados.nager.timeout', 5),
                    retryTimes: (int) config('services.feriados.nager.retry_times', 2),
                    retrySleep: (int) config('services.feriados.nager.retry_sleep', 200),
                ),
                default => throw new \InvalidArgumentException("Driver de feriados no soportado: {$driver}"),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureScramble();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Dolar\Commands\SyncExchangeRatesCommand::class,
                \App\Modules\Sismos\Commands\SyncSismosCommand::class,
                \App\Modules\Sismos\Commands\PurgeSismosCommand::class,
                \App\Modules\Feriados\Commands\AddFeriadoCommand::class,
                \App\Modules\Feriados\Commands\SyncFeriadosCommand::class,
                \App\Modules\Ruc\Commands\PurgeConsultasLogCommand::class,
            ]);
        }
    }

    private function configureScramble(): void
    {
        if (class_exists(Scramble::class)) {
            Scramble::configure()
                ->afterOpenApiGenerated(function (OpenApi $openApi) {
                    $scheme = SecurityScheme::apiKey('header', 'X-App-Key')
                        ->as('appKey')
                        ->setDescription('Clave de acceso opcional para la app móvil. Sin esta cabecera la API opera con límites públicos por IP.');

                    $openApi->components->addSecurityScheme('appKey', $scheme);
                });
        }
    }

    /**
     * Límites por IP o por clave de cliente autenticado (docs/07-seguridad-y-legal.md).
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            /** @var \App\Models\ClienteApi|null $client */
            $client = $request->attributes->get('cliente_api');

            if ($client !== null) {
                return Limit::perMinute($client->limite_por_minuto)
                    ->by('cliente_api:'.$client->id);
            }

            return Limit::perMinute((int) config('api.rate_limits.general', 60))
                ->by($request->ip());
        });

        RateLimiter::for('calculadoras', function (Request $request) {
            /** @var \App\Models\ClienteApi|null $client */
            $client = $request->attributes->get('cliente_api');

            if ($client !== null) {
                return Limit::perMinute($client->limite_por_minuto)
                    ->by('cliente_api:'.$client->id);
            }

            return Limit::perMinute((int) config('api.rate_limits.calculadoras', 30))
                ->by($request->ip());
        });

        RateLimiter::for('ruc', function (Request $request) {
            /** @var \App\Models\ClienteApi|null $client */
            $client = $request->attributes->get('cliente_api');
            $maxRuc = (int) config('api.rate_limits.ruc', 20);

            if ($client !== null) {
                return Limit::perMinute(min($client->limite_por_minuto, $maxRuc))
                    ->by('cliente_api:'.$client->id);
            }

            return Limit::perMinute($maxRuc)
                ->by($request->ip());
        });

        RateLimiter::for('dni', function (Request $request) {
            /** @var \App\Models\ClienteApi|null $client */
            $client = $request->attributes->get('cliente_api');
            $maxDni = (int) config('api.rate_limits.dni', 5);

            if ($client !== null) {
                return Limit::perMinute(min($client->limite_por_minuto, $maxDni))
                    ->by('cliente_api:'.$client->id);
            }

            return Limit::perMinute($maxDni)
                ->by($request->ip());
        });
    }
}
