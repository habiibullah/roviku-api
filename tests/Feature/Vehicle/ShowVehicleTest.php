<?php

namespace Tests\Feature\Vehicle;

use App\Models\Make;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowVehicleTest extends TestCase
{
    use RefreshDatabase;

    private function createVehicle(
        string $status = 'published',
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

        // Simulate an administrative publication decision.
        $vehicle->status = $status;
        $vehicle->published_at = $publishedAt;
        $vehicle->save();

        return $vehicle;
    }

    public function test_guest_can_view_published_vehicle(): void
    {
        $vehicle = $this->createVehicle(
            'published',
            now()->subDay()->toDateTimeString()
        );

        $response = $this->getJson(
            "/api/v1/vehicles/{$vehicle->slug}"
        );

        $response->assertOk()
            ->assertJsonPath('vehicle.id', $vehicle->id)
            ->assertJsonPath('vehicle.slug', $vehicle->slug)
            ->assertJsonPath('vehicle.make.name', 'Toyota')
            ->assertJsonPath('vehicle.vehicle_model.name', 'Camry');
    }

    public function test_guest_cannot_view_draft_vehicle(): void
    {
        $vehicle = $this->createVehicle('draft');

        $this->getJson("/api/v1/vehicles/{$vehicle->slug}")
            ->assertNotFound();
    }

    public function test_guest_cannot_view_pending_review_vehicle(): void
    {
        $vehicle = $this->createVehicle(
            'pending_review',
            now()->subDay()->toDateTimeString()
        );

        $this->getJson("/api/v1/vehicles/{$vehicle->slug}")
            ->assertNotFound();
    }

    public function test_guest_cannot_view_rejected_sold_or_archived_vehicle(): void
    {
        foreach (['rejected', 'sold', 'archived'] as $status) {
            $vehicle = $this->createVehicle(
                $status,
                now()->subDay()->toDateTimeString()
            );

            $this->getJson("/api/v1/vehicles/{$vehicle->slug}")
                ->assertNotFound();

            $vehicle->delete();
        }
    }

    public function test_vehicle_without_publication_date_is_hidden(): void
    {
        $vehicle = $this->createVehicle('published');

        $this->getJson("/api/v1/vehicles/{$vehicle->slug}")
            ->assertNotFound();
    }

    public function test_vehicle_with_future_publication_date_is_hidden(): void
    {
        $vehicle = $this->createVehicle(
            'published',
            now()->addDay()->toDateTimeString()
        );

        $this->getJson("/api/v1/vehicles/{$vehicle->slug}")
            ->assertNotFound();
    }

    public function test_nonexistent_vehicle_returns_not_found(): void
    {
        $this->getJson('/api/v1/vehicles/nonexistent-vehicle')
            ->assertNotFound();
    }
}
