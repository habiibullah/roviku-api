<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->status === 'active'
            && in_array(
                $user->role,
                [UserRole::SELLER, UserRole::DEALER],
                true
            );
    }

    public function rules(): array
    {
        return [
            'make_id' => [
                'required',
                'integer',
                Rule::exists('makes', 'id'),
            ],

            'vehicle_model_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_models', 'id')
                    ->where('make_id', $this->input('make_id')),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],

            'year' => [
                'required',
                'integer',
                'between:1886,2100',
            ],

            'price' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'mileage' => [
                'required',
                'integer',
                'min:0',
            ],

            'condition' => [
                'required',
                Rule::in(['new', 'used']),
            ],

            'transmission' => [
                'required',
                Rule::in(['automatic', 'manual', 'cvt', 'semi_automatic']),
            ],

            'fuel_type' => [
                'required',
                Rule::in(['petrol', 'diesel', 'hybrid', 'electric']),
            ],

            'body_type' => ['nullable', 'string', 'max:255'],
            'drive_type' => ['nullable', 'string', 'max:255'],

            'engine_size' => [
                'nullable',
                'numeric',
                'between:0,99.9',
                'decimal:0,1',
            ],

            'exterior_color' => ['nullable', 'string', 'max:255'],
            'interior_color' => ['nullable', 'string', 'max:255'],

            'vin' => [
                'nullable',
                'string',
                'size:17',
                Rule::unique('vehicles', 'vin'),
            ],

            'location' => ['required', 'string', 'max:255'],
        ];
    }
}
