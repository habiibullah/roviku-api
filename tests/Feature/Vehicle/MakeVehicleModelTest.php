<?php

namespace Tests\Feature\Vehicle;

use App\Models\Make;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class MakeVehicleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_can_have_multiple_vehicle_models(): void
    {
        $make = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Corolla',
            'slug' => 'corolla',
        ]);

        $this->assertCount(2, $make->vehicleModels);
    }

    public function test_vehicle_model_belongs_to_make(): void
    {
        $make = Make::create([
            'name' => 'Honda',
            'slug' => 'honda',
        ]);

        $vehicleModel = VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Accord',
            'slug' => 'accord',
        ]);

        $this->assertTrue($vehicleModel->make->is($make));
        $this->assertEquals('Honda', $vehicleModel->make->name);
    }

    public function test_duplicate_model_name_for_same_make_is_rejected(): void
    {
        $make = Make::create([
            'name' => 'Toyota',
            'slug' => 'toyota',
        ]);

        VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Camry',
            'slug' => 'camry',
        ]);

        $this->expectException(QueryException::class);

        VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'Camry',
            'slug' => 'camry-duplicate',
        ]);
    }

    public function test_deleting_make_deletes_its_vehicle_models(): void
    {
        $make = Make::create([
            'name' => 'BMW',
            'slug' => 'bmw',
        ]);

        $vehicleModel = VehicleModel::create([
            'make_id' => $make->id,
            'name' => 'X5',
            'slug' => 'x5',
        ]);

        $modelId = $vehicleModel->id;

        $make->delete();

        $this->assertDatabaseMissing('vehicle_models', [
            'id' => $modelId,
        ]);
    }
}
