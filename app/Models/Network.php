<?php

namespace App\Models;

use App\Enums\ChainType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Network extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => ChainType::class,
    ];
}
