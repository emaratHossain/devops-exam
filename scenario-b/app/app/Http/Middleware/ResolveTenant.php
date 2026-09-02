<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the X-Tenant header, finds the tenant, and puts its id on the request.
 * Controllers read it with $request->attributes->get('tenant_id').
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = trim((string) $request->header('X-Tenant'));

        if ($slug === '') {
            return response()->json([
                'message' => 'The X-Tenant header is required.',
            ], 400);
        }

        $tenant = DB::table('tenants')->where('slug', $slug)->first(['id', 'slug']);

        if ($tenant === null) {
            return response()->json([
                'message' => "Unknown tenant [{$slug}].",
            ], 404);
        }

        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant_slug', $tenant->slug);

        return $next($request);
    }
}
