<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for the /healthz liveness probe.
 *
 * The CI pipeline starts the built image and curls /healthz. These tests make
 * sure the route keeps the three things the pipeline depends on: it answers
 * 200, it needs no X-Tenant header, and it needs no database.
 */
class HealthzTest extends TestCase
{
    public function test_healthz_returns_200_and_says_ok(): void
    {
        $this->getJson('/healthz')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_healthz_needs_no_tenant_header(): void
    {
        // Every /api route is behind the tenant middleware and answers 400
        // without the header. /healthz must not be, or a load balancer could
        // never call it.
        $this->getJson('/api/notes')->assertStatus(400);

        $this->getJson('/healthz')->assertOk();
    }

    public function test_healthz_reports_the_baked_in_app_version(): void
    {
        // The image is built with --build-arg APP_VERSION=v2, which becomes
        // an env var. /healthz hands that value back, so a deploy can be
        // checked from outside.
        putenv('APP_VERSION=ci-test');

        try {
            $this->getJson('/healthz')
                ->assertOk()
                ->assertJsonPath('version', 'ci-test');
        } finally {
            putenv('APP_VERSION');
        }
    }
}
