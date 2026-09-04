<?php

namespace App\Support;

use Illuminate\Http\Request;

class MetricLabels
{
    /**
     * The route PATTERN, never the real URL.
     * "api/notes/{id}"  ->  "/api/notes/:id"
     * No matching route ->  "/unmatched"  (never the raw path)
     */
    public static function route(Request $request): string
    {
        $route = $request->route();

        if ($route === null || $route->uri() === null) {
            return '/unmatched';
        }

        // {id} and {id?} both become :id
        $uri = preg_replace('/\{(\w+)\??\}/', ':$1', $route->uri());

        return '/'.ltrim((string) $uri, '/');
    }

    /**
     * The tenant slug, but only when ResolveTenant found a real tenant.
     * A bad or missing X-Tenant header gives the fixed value "unknown".
     */
    public static function tenant(Request $request): string
    {
        $slug = $request->attributes->get('tenant_slug');

        return is_string($slug) && $slug !== '' ? $slug : 'unknown';
    }

    /**
     * A short name for a SQL statement: verb + main table.
     * 'select count(*) as aggregate from "notes" where ...' -> "count_notes"
     * 'select "name" from "tags" where "note_id" = ?'       -> "select_tags"
     */
    public static function queryName(string $sql): string
    {
        $sql = strtolower(trim(preg_replace('/\s+/', ' ', $sql) ?? ''));

        $verb = match (true) {
            str_starts_with($sql, 'select count(') => 'count',
            str_starts_with($sql, 'select')        => 'select',
            str_starts_with($sql, 'insert')        => 'insert',
            str_starts_with($sql, 'update')        => 'update',
            str_starts_with($sql, 'delete')        => 'delete',
            default                                => 'other',
        };

        $table = 'unknown';

        if (preg_match('/\b(?:from|into|update)\s+"?([a-z0-9_]+)"?/', $sql, $m) === 1) {
            $table = $m[1];
        }

        return $verb.'_'.$table;
    }
}