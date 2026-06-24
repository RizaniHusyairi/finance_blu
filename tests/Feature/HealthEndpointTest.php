<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_aggregate_status_without_token(): void
    {
        $response = $this->getJson('/health');

        // 200 (ok/degraded) atau 503 (down) — keduanya valid, selalu JSON agregat.
        $this->assertContains($response->getStatusCode(), [200, 503]);
        $response->assertJsonStructure(['status', 'checked_at']);
        $this->assertContains($response->json('status'), ['ok', 'degraded', 'down']);

        // Tanpa token, detail per-probe TIDAK boleh bocor ke publik.
        $this->assertArrayNotHasKey('checks', $response->json());
        $this->assertArrayNotHasKey('summary', $response->json());
    }

    public function test_health_exposes_probe_detail_with_valid_token(): void
    {
        $token = 'test-health-secret-123';
        $this->withHealthToken($token);

        try {
            $response = $this->getJson('/health?token=' . $token);

            $response->assertJsonStructure([
                'status',
                'checked_at',
                'summary' => ['ok', 'warn', 'critical'],
                'checks' => [['key', 'label', 'status', 'detail', 'latency_ms']],
            ]);

            $keys = array_column($response->json('checks'), 'key');
            $this->assertContains('database', $keys);
            $this->assertContains('scheduler', $keys);
            $this->assertContains('backup', $keys);
        } finally {
            $this->clearHealthToken();
        }
    }

    public function test_health_hides_detail_with_wrong_token(): void
    {
        $this->withHealthToken('correct-token');

        try {
            $response = $this->getJson('/health?token=salah');

            $this->assertArrayNotHasKey('checks', $response->json());
        } finally {
            $this->clearHealthToken();
        }
    }

    private function withHealthToken(string $token): void
    {
        putenv("MONITORING_HEALTH_TOKEN={$token}");
        $_ENV['MONITORING_HEALTH_TOKEN'] = $token;
        $_SERVER['MONITORING_HEALTH_TOKEN'] = $token;
    }

    private function clearHealthToken(): void
    {
        putenv('MONITORING_HEALTH_TOKEN');
        unset($_ENV['MONITORING_HEALTH_TOKEN'], $_SERVER['MONITORING_HEALTH_TOKEN']);
    }
}
