<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'station_id',
    'fuel_type_id',
    'stock_liters',
    'capacity_liters',
    'low_stock_threshold',
    'updated_by',
])]
class FuelStock extends Model
{
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }
}
