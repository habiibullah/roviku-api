<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
            'role' => UserRole::BUYER,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('user.email', 'buyer@example.com')
            ->assertJsonPath('user.role', 'buyer')
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
            'role' => UserRole::BUYER,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_user_cannot_login_with_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'Password123!',
            'role' => UserRole::BUYER,
            'status' => 'suspended',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is not active.');
    }
}
