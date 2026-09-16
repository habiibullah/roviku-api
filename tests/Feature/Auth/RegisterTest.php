<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'buyer',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('user.email', 'buyer@example.com')
            ->assertJsonPath('user.role', 'buyer')
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'buyer@example.com',
            'role' => 'buyer',
            'status' => 'active',
        ]);
    }

    public function test_password_is_hashed_during_registration(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'buyer',
        ])->assertCreated();

        $user = User::where('email', 'buyer@example.com')->firstOrFail();

        $this->assertTrue(
            Hash::check('Password123!', $user->password)
        );

        $this->assertNotSame('Password123!', $user->password);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'role' => UserRole::BUYER,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another Buyer',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'buyer',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_cannot_register_through_public_registration(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Fake Admin',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }
}
