<?php

namespace Tests\Feature\Vehicle;

use App\Models\Make;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListVehiclesTest extends TestCase
{
    use RefreshDatabase;

    private function createVehicle(
        string $status,
        ?string $publishedAt = null
    ): Vehicle {
        $user = User::factory()->create();

        $make = Make::firstOrCreate(
            ['slug' => 'toyota'],
            ['name' => 'Toyota']
        );

        $model = VehicleModel::firstOrCreate(
            [
                'make_id' => $make->id,
                'slug' => 'camry',
            ],
            ['name' => 'Camry']
        );

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'make_id' => $make->id,
            'vehicle_model_id' => $model->id,
            'title' => 'Toyota Camry 2020',
            'slug' => 'toyota-camry-2020-' . \Illuminate\Support\Str::random(12),
            'year' => 2020,
            'price' => '12500000.00',
            'mileage' => 45000,
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'location' => 'Lagos',
        ]);

        // Simulate a listing status change performed by an administrator.
        $vehicle->status = $status;
        $vehicle->published_at = $publishedAt;
        $vehicle->save();

        return $vehicle;
    }

    public function test_guest_can_list_published_vehicles(): void
    {
        $vehicle = $this->createVehicle(
            'published',
            now()->subDay()->toDateTimeString()
        );

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $vehicle->id)
            ->assertJsonPath('data.0.make.name', 'Toyota')
            ->assertJsonPath('data.0.vehicle_model.name', 'Camry');
    }

    public function test_guest_cannot_see_draft_vehicle(): void
    {
        $this->createVehicle('draft');

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_see_unpublished_vehicle_statuses(): void
    {
        foreach (['pending_review', 'rejected', 'sold', 'archived'] as $status) {
            $vehicle = $this->createVehicle(
                'draft',
                now()->subDay()->toDateTimeString()
            );

            $vehicle->status = $status;
            $vehicle->save();
        }

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_vehicle_without_publication_date_is_hidden(): void
    {
        $this->createVehicle('published');

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_vehicle_with_future_publication_date_is_hidden(): void
    {
        $this->createVehicle(
            'published',
            now()->addDay()->toDateTimeString()
        );

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_public_vehicle_list_is_paginated(): void
    {
        $this->createVehicle(
            'published',
            now()->subDay()->toDateTimeString()
        );

        $response = $this->getJson('/api/v1/vehicles');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
                'last_page',
            ])
            ->assertJsonPath('per_page', 12)
            ->assertJsonPath('total', 1);
    }
}
