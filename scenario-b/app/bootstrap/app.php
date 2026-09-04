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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
