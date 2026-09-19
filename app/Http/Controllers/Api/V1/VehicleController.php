<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($user->role === UserRole::DEALER) {
            $dealer = $user->dealer;

            if (
                $dealer === null ||
                $dealer->verification_status !== 'verified'
            ) {
                return response()->json([
                    'message' => 'A verified dealer profile is required to create a dealer listing.',
                ], 403);
            }

            $data['dealer_id'] = $dealer->id;
        }

        $data['user_id'] = $user->id;

        $data['slug'] = Str::slug($data['title'])
            . '-' . Str::lower(Str::random(12));

        $vehicle = Vehicle::create($data);

        return response()->json([
            'message' => 'Vehicle listing created successfully.',
            'vehicle' => $vehicle->load([
                'make',
                'vehicleModel',
                'dealer',
            ]),
        ], 201);
    }
}
