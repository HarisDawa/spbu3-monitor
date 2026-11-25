<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'fuel_stock_id',
    'station_id',
    'fuel_name',
    'previous_stock',
    'new_stock',
    'change_amount',
    'note',
    'updated_by',
])]
class StockHistory extends Model
{
    public function fuelStock(): BelongsTo
    {
        return $this->belongsTo(FuelStock::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
