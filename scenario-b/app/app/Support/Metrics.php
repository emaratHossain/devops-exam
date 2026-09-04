<?php

namespace App\Support;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;

class Metrics
{
    private CollectorRegistry $registry;

    /** How many SQL queries the current request has run so far. */
    private int $queryCount = 0;

    public function __construct()
    {
        // APCu keeps the numbers alive between requests.
        // InMemory is a fallback so the app never crashes if apcu is missing.
        $storage = extension_loaded('apcu') && apcu_enabled()
            ? new APC()
            : new InMemory();

        // false = do not add the library's own default php_info metric.
        $this->registry = new CollectorRegistry($storage, false);
    }

    // ---------- HTTP ----------

    public function observeRequest(
        string $route,
        string $method,
        int $status,
        string $tenant,
        float $seconds
    ): void {
        $this->registry
            ->getOrRegisterCounter('', 'http_requests_total',
                'Total HTTP requests.',
                ['route', 'method', 'status', 'tenant'])
            ->inc([$route, $method, (string) $status, $tenant]);

        $this->registry
            ->getOrRegisterHistogram('', 'http_request_duration_seconds',
                'HTTP request duration in seconds.',
                ['route', 'method', 'tenant'],
                [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5])
            ->observe($seconds, [$route, $method, $tenant]);
    }

    public function inFlightInc(): void
    {
        $this->inFlight()->inc();
    }

    public function inFlightDec(): void
    {
        $this->inFlight()->dec();
    }

    // ---------- Database ----------

    public function observeQuery(string $queryName, float $seconds): void
    {
        $this->queryCount++;

        $this->registry
            ->getOrRegisterHistogram('', 'db_query_duration_seconds',
                'SQL query duration in seconds.',
                ['query_name'],
                [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1])
            ->observe($seconds, [$queryName]);
    }

    /** How many rows one query gave back. Catches a missing LIMIT. */
    public function observeRows(string $queryName, int $rows): void
    {
        $this->registry
            ->getOrRegisterHistogram('', 'db_rows_returned',
                'Rows returned by one SQL query.',
                ['query_name'],
                [1, 5, 10, 20, 50, 100, 500, 1000, 5000])
            ->observe((float) $rows, [$queryName]);
    }

    /** How many queries one HTTP request ran. This is the N+1 detector. */
    public function observeQueriesPerRequest(string $route, int $count): void
    {
        $this->registry
            ->getOrRegisterHistogram('', 'db_queries_per_request',
                'SQL queries run by one HTTP request.',
                ['route'],
                [1, 2, 5, 10, 20, 50, 100, 200, 500])
            ->observe((float) $count, [$route]);
    }

    public function queryCount(): int
    {
        return $this->queryCount;
    }

    public function resetQueryCount(): void
    {
        $this->queryCount = 0;
    }

    // ---------- Output ----------

    public function render(): string
    {
        return (new RenderTextFormat())->render($this->registry->getMetricFamilySamples());
    }

    public static function contentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }

    private function inFlight(): \Prometheus\Gauge
    {
        return $this->registry->getOrRegisterGauge('', 'http_requests_in_flight',
            'HTTP requests being handled right now.');
    }
}