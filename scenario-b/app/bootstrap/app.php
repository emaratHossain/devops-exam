<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Registered here, not in routes/api.php, so the path stays "/metrics"
        // and it gets no session middleware.
        then: function (): void {
            Route::get('/metrics', App\Http\Controllers\MetricsController::class);

            // Liveness probe for the CI pipeline and for any load balancer.
            // Registered here on purpose: it gets no session and no tenant
            // middleware, and it touches no database. So it answers 200 as
            // long as PHP itself is up. That is exactly what "is the
            // container alive?" means.
            Route::get('/healthz', fn () => response()->json([
                'status' => 'ok',
                'version' => getenv('APP_VERSION') ?: 'unknown',
            ]));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => App\Http\Middleware\ResolveTenant::class,
        ]);

        // First in the chain, so a 400 or 404 from ResolveTenant is counted too.
        $middleware->api(prepend: [
            App\Http\Middleware\TrackMetrics::class,
        ]);

        // Runs on every request, so /up gets it too. Adds X-Served-By: <container id>.
        $middleware->append(App\Http\Middleware\ServedBy::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
