<?php

use App\Support\Exceptions\ApiExceptionRenderer;
use App\Support\Http\Middleware\ForceJson;
use App\Support\Http\Middleware\SetPublicCacheHeaders;
use App\Support\Http\Middleware\ValidateAppKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Toda petición a /api/* se trata como JSON y valida la cabecera opcional X-App-Key.
        $middleware->api(prepend: [
            ForceJson::class,
            ValidateAppKey::class,
        ]);

        // Límite general por IP (limitador "api", definido en AppServiceProvider).
        $middleware->throttleApi();

        $middleware->alias([
            'cache.public' => SetPublicCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Formato de error estándar (docs/02-arquitectura.md).
        $exceptions->render(new ApiExceptionRenderer());
    })->create();
