<?php

namespace App\Http\Middleware;

use App\Support\MetricLabels;
use App\Support\Metrics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackMetrics
{
    public function __construct(private Metrics $metrics)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->metrics->resetQueryCount();
        $this->metrics->inFlightInc();

        $start = microtime(true);
        $status = 500; // if the app throws, we still record a 500

        try {
            /** @var Response $response */
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } finally {
            $seconds = microtime(true) - $start;
            $this->metrics->inFlightDec();

            $route = MetricLabels::route($request);
            $tenant = MetricLabels::tenant($request);
            $queries = $this->metrics->queryCount();

            $this->metrics->observeRequest($route, $request->method(), $status, $tenant, $seconds);
            $this->metrics->observeQueriesPerRequest($route, $queries);

            Log::info('http_request', [
                'route' => $route,
                'method' => $request->method(),
                'status' => $status,
                'tenant' => $tenant,
                'duration_ms' => round($seconds * 1000, 2),
                'db_queries' => $queries,
            ]);
        }
    }
}
