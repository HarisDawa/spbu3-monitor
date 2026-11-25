<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'id',
    'code',
    'name',
    'address',
    'region',
    'latitude',
    'longitude',
    'phone',
    'opening_hours',
    'operational_status',
    'facility',
])]
class Station extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function fuelStocks(): HasMany
    {
        return $this->hasMany(FuelStock::class);
    }

    public function queueStatus(): HasOne
    {
        return $this->hasOne(QueueStatus::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CitizenReport::class);
    }
}
