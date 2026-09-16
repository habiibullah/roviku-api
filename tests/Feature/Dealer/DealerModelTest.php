<?php

namespace Tests\Feature\Dealer;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealer_belongs_to_user(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        $dealer = Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Roviku Motors',
            'slug' => 'roviku-motors',
            'phone' => '08000000000',
            'address' => 'Nigeria',
        ]);

        $this->assertTrue($dealer->user->is($user));
    }

    public function test_user_has_one_dealer_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        $dealer = Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Prime Autos',
            'slug' => 'prime-autos',
        ]);

        $this->assertTrue($user->dealer->is($dealer));
    }

    public function test_dealer_verification_status_defaults_to_pending(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        $dealer = Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'City Motors',
            'slug' => 'city-motors',
	]);

	$dealer->refresh();

        $this->assertEquals('pending', $dealer->verification_status);
    }

    public function test_user_cannot_have_multiple_dealer_profiles(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'First Motors',
            'slug' => 'first-motors',
        ]);

        $this->expectException(QueryException::class);

        Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Second Motors',
            'slug' => 'second-motors',
        ]);
    }

    public function test_deleting_user_deletes_dealer_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        $dealer = Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Auto World',
            'slug' => 'auto-world',
        ]);

        $dealerId = $dealer->id;

        $user->delete();

        $this->assertDatabaseMissing('dealers', [
            'id' => $dealerId,
        ]);
    }

    public function test_verification_status_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        $dealer = Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Secure Motors',
            'slug' => 'secure-motors',
            'verification_status' => 'verified',
	]);

	$dealer->refresh();

        $this->assertEquals('pending', $dealer->verification_status);
    }
}
