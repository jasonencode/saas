<?php

namespace App\Models;

use App\Enums\RegionLevel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Region extends Model
{
    protected $casts = [
        'level' => RegionLevel::class,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
