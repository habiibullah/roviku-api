<?php

namespace Tests\Feature\Vehicle;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\Make;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateVehicleTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        $make = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        $model = VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        return [
            'make_id' => $make->id,
            'vehicle_model_id' => $model->id,
            'title' => 'Toyota Camry 2020',
            'description' => 'A well-maintained vehicle.',
            'year' => 2020,
            'price' => '12500000.00',
            'mileage' => 45000,
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'location' => 'Lagos',
        ];
    }

    public function test_seller_can_create_draft_vehicle_listing(): void
    {
        $seller = User::factory()->create([
            'role' => UserRole::SELLER,
        ]);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('vehicle.user_id', $seller->id)
            ->assertJsonPath('vehicle.status', 'draft')
            ->assertJsonPath('vehicle.is_featured', false);

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $seller->id,
            'title' => 'Toyota Camry 2020',
            'status' => 'draft',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_vehicle(): void
    {
        $response = $this->postJson(
            '/api/v1/vehicles',
            $this->validPayload()
        );

        $response->assertUnauthorized();

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_buyer_cannot_create_vehicle(): void
    {
        $buyer = User::factory()->create([
            'role' => UserRole::BUYER,
        ]);

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->validPayload());

        $response->assertForbidden();

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_seller_cannot_assign_another_user_as_owner(): void
    {
        $seller = User::factory()->create([
            'role' => UserRole::SELLER,
        ]);

        $otherUser = User::factory()->create();

        $payload = $this->validPayload();
        $payload['user_id'] = $otherUser->id;

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/vehicles', $payload);

        $response->assertCreated()
            ->assertJsonPath('vehicle.user_id', $seller->id);

        $this->assertDatabaseMissing('vehicles', [
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_seller_cannot_publish_or_feature_listing(): void
    {
        $seller = User::factory()->create([
            'role' => UserRole::SELLER,
        ]);

        $payload = $this->validPayload();

        $payload['status'] = 'published';
        $payload['is_featured'] = true;
        $payload['published_at'] = now()->toDateTimeString();

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/vehicles', $payload);

        $response->assertCreated()
            ->assertJsonPath('vehicle.status', 'draft')
            ->assertJsonPath('vehicle.is_featured', false)
            ->assertJsonPath('vehicle.published_at', null);
    }

    public function test_vehicle_model_must_belong_to_selected_make(): void
    {
        $seller = User::factory()->create([
            'role' => UserRole::SELLER,
        ]);

        $payload = $this->validPayload();

        $honda = Make::create([
            'name' => 'Honda',
            'slug' => 'honda',
        ]);

        $accord = VehicleModel::create([
            'make_id' => $honda->id,
            'name' => 'Accord',
            'slug' => 'accord',
        ]);

        $payload['vehicle_model_id'] = $accord->id;

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/vehicles', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_model_id']);

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_unverified_dealer_cannot_create_listing(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Prime Autos',
            'slug' => 'prime-autos',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->validPayload());

        $response->assertForbidden();

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_verified_dealer_can_create_listing(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::DEALER,
        ]);

        $dealer = Dealer::create([
            'user_id' => $user->id,
            'business_name' => 'Roviku Motors',
            'slug' => 'roviku-motors',
        ]);

        // Simulate a verification action performed by an administrator.
        $dealer->verification_status = 'verified';
        $dealer->save();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('vehicle.user_id', $user->id)
            ->assertJsonPath('vehicle.dealer_id', $dealer->id);

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $user->id,
            'dealer_id' => $dealer->id,
        ]);
    }
}
