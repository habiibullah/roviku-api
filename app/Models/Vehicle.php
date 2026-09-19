<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dealer_id',
        'make_id',
        'vehicle_model_id',
        'title',
        'slug',
        'description',
        'year',
        'price',
        'mileage',
        'condition',
        'transmission',
        'fuel_type',
        'body_type',
        'drive_type',
        'engine_size',
        'exterior_color',
        'interior_color',
        'vin',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price' => 'decimal:2',
            'mileage' => 'integer',
            'engine_size' => 'decimal:1',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(Make::class);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }
}
