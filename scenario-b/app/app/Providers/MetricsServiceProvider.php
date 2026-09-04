<?php

namespace App\Providers;

use App\Support\MetricLabels;
use App\Support\Metrics;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One Metrics object for the whole request.
        $this->app->singleton(Metrics::class);
    }

    public function boot(): void
    {
        // Fires after every SQL query Laravel runs. No controller change needed.
        DB::listen(function (QueryExecuted $query): void {
            /** @var Metrics $metrics */
            $metrics = $this->app->make(Metrics::class);

            // $query->time is milliseconds. Prometheus wants seconds.
            $metrics->observeQuery(MetricLabels::queryName($query->sql), $query->time / 1000);
        });
    }
}
