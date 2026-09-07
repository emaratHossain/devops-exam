<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServedBy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // A container's hostname is its container ID. Every copy has a
        // different one. This shows which copy answered the request.
        $response->headers->set('X-Served-By', gethostname());

        // Baked into the image at build time:
        //   docker build --build-arg APP_VERSION=v2 ...
        $response->headers->set('X-App-Version', getenv('APP_VERSION') ?: 'unknown');

        return $response;
    }
}
