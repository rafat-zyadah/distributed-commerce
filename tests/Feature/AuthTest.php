<?php

namespace Tests\Feature;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_a_customer_and_returns_a_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ])
            ->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'ali@example.com', 'role' => 'customer']);
    }

    public function test_client_cannot_register_itself_as_admin(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'role' => 'admin',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'eve@example.com', 'role' => 'customer']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'u@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'u@example.com', 'password' => 'wrong'])
            ->assertUnauthorized();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'u@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'u@example.com', 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'u@example.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_protected_route_rejects_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_protected_route_rejects_a_garbage_token(): void
    {
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer not-a-real-token'])
            ->assertUnauthorized();
    }

    public function test_responses_carry_security_headers(): void
    {
        // A 401 from a protected route. The product catalog sets its own Cache-Control (see PerformanceTest).
        $this->getJson('/api/v1/auth/me')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Cache-Control', 'no-store, private');
    }
}
