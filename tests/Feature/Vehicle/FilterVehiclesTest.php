<?php

namespace Tests\Feature\Vehicle;

use App\Models\Make;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterVehiclesTest extends TestCase
{
    use RefreshDatabase;

    private function createVehicle(
        Make $make,
        VehicleModel $model,
        string $status = 'published'
    ): Vehicle {
        $vehicle = Vehicle::create([
            'user_id' => User::factory()->create()->id,
            'make_id' => $make->id,
            'vehicle_model_id' => $model->id,
            'title' => $make->name . ' ' . $model->name,
            'slug' => 'test-vehicle-' . \Illuminate\Support\Str::random(12),
            'year' => 2020,
            'price' => '12500000.00',
            'mileage' => 45000,
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'location' => 'Lagos',
        ]);

        $vehicle->status = $status;
        $vehicle->published_at = now()->subDay();
        $vehicle->save();

        return $vehicle;
    }

    public function test_guest_can_filter_vehicles_by_make(): void
    {
        $toyota = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        $honda = Make::create([
            'name' => 'Honda',
            'slug' => 'honda',
        ]);

        $camry = VehicleModel::create([
            'make_id' => $toyota->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        $civic = VehicleModel::create([
            'make_id' => $honda->id,
            'name' => 'Civic',
            'slug' => 'civic',
        ]);

        $toyotaVehicle = $this->createVehicle($toyota, $camry);
        $this->createVehicle($honda, $civic);

        $this->getJson("/api/v1/vehicles?make_id={$toyota->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $toyotaVehicle->id);
    }

    public function test_guest_can_filter_vehicles_by_model(): void
    {
        $toyota = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        $camry = VehicleModel::create([
            'make_id' => $toyota->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        $corolla = VehicleModel::create([
            'make_id' => $toyota->id,
            'name' => 'Corolla',
            'slug' => 'corolla',
        ]);

        $camryVehicle = $this->createVehicle($toyota, $camry);
        $this->createVehicle($toyota, $corolla);

        $this->getJson("/api/v1/vehicles?vehicle_model_id={$camry->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $camryVehicle->id);
    }

    public function test_make_and_model_filters_must_both_match(): void
    {
        $toyota = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        $honda = Make::create([
            'name' => 'Honda',
            'slug' => 'honda',
        ]);

        $camry = VehicleModel::create([
            'make_id' => $toyota->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        $civic = VehicleModel::create([
            'make_id' => $honda->id,
            'name' => 'Civic',
            'slug' => 'civic',
        ]);

        $this->createVehicle($toyota, $camry);
        $this->createVehicle($honda, $civic);

        $this->getJson(
            "/api/v1/vehicles?make_id={$toyota->id}&vehicle_model_id={$civic->id}"
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_filters_never_expose_unpublished_vehicles(): void
    {
        $toyota = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        $camry = VehicleModel::create([
            'make_id' => $toyota->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        $this->createVehicle($toyota, $camry, 'draft');

        $this->getJson(
            "/api/v1/vehicles?make_id={$toyota->id}&vehicle_model_id={$camry->id}"
        )
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        foreach ([
            '?make_id=abc',
            '?make_id=-1',
            '?make_id=',
            '?vehicle_model_id=abc',
            '?vehicle_model_id=-1',
            '?vehicle_model_id=',
        ] as $queryString) {
            $this->getJson('/api/v1/vehicles' . $queryString)
                ->assertUnprocessable();
        }
    }
}
