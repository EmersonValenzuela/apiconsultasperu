<?php

namespace App\Providers;

use App\Modules\Dolar\Clients\OpenErApiClient;
use App\Modules\Dolar\Contracts\ExchangeRateProvider;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Límites por IP (docs/07-seguridad-y-legal.md). Usan el store de caché configurado.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('api.rate_limits.general'))
                ->by($request->ip());
        });
    }
}
