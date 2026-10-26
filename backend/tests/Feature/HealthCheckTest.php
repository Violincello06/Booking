<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test health check endpoint.
     */
    public function test_health_check_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sistem Travel Booking API berjalan dengan baik',
                'data' => [
                    'status' => 'healthy',
                    'database' => [
                        'connected' => true,
                    ],
                    'sanctum' => [
                        'installed' => true,
                        'tokens_table_ready' => true,
                    ],
                ],
            ]);
    }

    /**
     * Test protected route rejects unauthenticated request with 401.
     */
    public function test_protected_route_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    /**
     * Test protected route allows authenticated user with Sanctum token.
     */
    public function test_protected_route_accepts_sanctum_authenticated_user(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }
}
