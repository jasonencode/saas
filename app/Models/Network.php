<?php

namespace App\Models;

use App\Enums\ChainType;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Network extends Model
{
    use HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => ChainType::class,
    ];
}
