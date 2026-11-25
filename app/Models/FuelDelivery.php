<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelDelivery extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'station_id',
        'fuel_type_id',
        'vehicle_name',
        'vehicle_plate',
        'driver_name',
        'origin_depot',
        'origin_lat',
        'origin_lng',
        'current_lat',
        'current_lng',
        'quantity_liters',
        'status',
        'eta_minutes',
        'departed_at',
        'delivered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'origin_lat' => 'float',
            'origin_lng' => 'float',
            'current_lat' => 'float',
            'current_lng' => 'float',
            'quantity_liters' => 'integer',
            'eta_minutes' => 'integer',
            'departed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }
}
