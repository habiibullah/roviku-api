<?php

namespace Tests\Feature\Vehicle;

use App\Models\Dealer;
use App\Models\Make;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleModelTest extends TestCase
{
    use RefreshDatabase;

    private function createVehicle(array $attributes = []): Vehicle
    {
        $user = User::factory()->create();

        $make = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        $vehicleModel = VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        return Vehicle::create(array_merge([
            'user_id' => $user->id,
            'make_id' => $make->id,
            'vehicle_model_id' => $vehicleModel->id,
            'title' => 'Toyota Camry 2020',
            'slug' => 'toyota-camry-2020',
            'year' => 2020,
            'price' => '12500000.00',
            'mileage' => 45000,
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'location' => 'Lagos',
        ], $attributes));
    }

    public function test_vehicle_belongs_to_its_owner_make_and_model(): void
    {
        $vehicle = $this->createVehicle();

        $this->assertTrue($vehicle->user->vehicles->contains($vehicle));
        $this->assertEquals('Toyota', $vehicle->make->name);
        $this->assertEquals('Camry', $vehicle->vehicleModel->name);
        $this->assertTrue($vehicle->make->vehicles->contains($vehicle));
        $this->assertTrue($vehicle->vehicleModel->vehicles->contains($vehicle));
    }

    public function test_vehicle_can_belong_to_a_dealer(): void
    {
        $vehicle = $this->createVehicle();

        $dealer = Dealer::create([
            'user_id' => $vehicle->user_id,
            'business_name' => 'Roviku Motors',
            'slug' => 'roviku-motors',
        ]);

        $vehicle->dealer_id = $dealer->id;
        $vehicle->save();

        $this->assertTrue($vehicle->dealer->is($dealer));
        $this->assertTrue($dealer->vehicles->contains($vehicle));
    }

    public function test_individual_seller_can_create_vehicle_without_dealer(): void
    {
        $vehicle = $this->createVehicle();

        $this->assertNull($vehicle->dealer_id);
        $this->assertNull($vehicle->dealer);
    }

    public function test_new_vehicle_defaults_to_draft_and_not_featured(): void
    {
        $vehicle = $this->createVehicle([
            'status' => 'published',
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $vehicle->refresh();

        $this->assertEquals('draft', $vehicle->status);
        $this->assertFalse($vehicle->is_featured);
        $this->assertNull($vehicle->published_at);
    }

    public function test_vehicle_price_is_stored_with_two_decimal_places(): void
    {
        $vehicle = $this->createVehicle();

        $this->assertSame('12500000.00', $vehicle->price);
    }
}
