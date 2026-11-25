<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'station_id',
    'vehicle_category',
    'estimated_wait_minutes',
    'queue_level',
    'source',
    'updated_by',
])]
class QueueStatus extends Model
{
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
