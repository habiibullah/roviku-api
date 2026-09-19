<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'make_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'vehicle_model_id' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        $query = Vehicle::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with([
                    'make',
                    'vehicleModel',
                    'dealer',
            ]);

            if (isset($filters['make_id'])) {
                $query->where('make_id', $filters['make_id']);
            }

            if (isset($filters['vehicle_model_id'])) {
                $query->where(
                    'vehicle_model_id',
                    $filters['vehicle_model_id']
                );
            }

            $vehicles = $query
                ->latest('published_at')
                ->paginate(12);

            return response()->json($vehicles);
    }

    public function show(string $slug): JsonResponse
    {
        $vehicle = Vehicle::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with([
                'make',
                'vehicleModel',
                'dealer',
            ])
            ->firstOrFail();

            return response()->json([
                'vehicle' => $vehicle,
            ]);
    }
}
