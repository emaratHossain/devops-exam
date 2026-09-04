<?php

namespace App\Http\Controllers;

use App\Support\Metrics;
use Illuminate\Http\Response;

/** GET /metrics - the page Prometheus reads every 15 seconds. */
class MetricsController extends Controller
{
    public function __invoke(Metrics $metrics): Response
    {
        return new Response($metrics->render(), 200, [
            'Content-Type' => Metrics::contentType(),
        ]);
    }
}
