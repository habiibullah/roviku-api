<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'role' => UserRole::BUYER,
        ]);

        $token = $user->createToken('roviku-api')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'buyer@example.com')
            ->assertJsonPath('user.role', 'buyer');
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::BUYER,
        ]);

        $token = $user->createToken('roviku-api')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logged_out_token_cannot_access_profile(): void
    {
        $user = User::factory()->create([
        'role' => UserRole::BUYER,
    ]);

        $token = $user->createToken('roviku-api')->plainTextToken;

        $this
            ->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    }
}
