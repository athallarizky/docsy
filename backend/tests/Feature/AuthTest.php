<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_admin_can_login_and_receives_administrator_role(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'administrator')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]]);
    }

    public function test_viewer_can_login_and_receives_viewer_role(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.role', 'viewer');
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_login_with_missing_fields_returns_validation_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_me_and_logout_revokes_token(): void
    {
        $user = User::whereEmail('admin@example.com')->first();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@example.com');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // antar-request dalam satu test berbagi SATU proses → guard sanctum
        // meng-cache user di memori. forgetGuards() mereplikasi boundary
        // proses baru (seperti request php-fpm sesungguhnya) sebelum assert.
        // RCA: docs/sprint-1/rca/2026-09-09-sanctum-guard-cache-in-tests.md
        $this->app->make(\Illuminate\Auth\AuthManager::class)->forgetGuards();

        // token yang sudah dicabut tidak bisa dipakai lagi
        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }
}
