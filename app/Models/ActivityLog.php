<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_name',
    'user_role',
    'action',
    'entity_type',
    'entity_id',
    'description',
])]
class ActivityLog extends Model
{
    //
}
