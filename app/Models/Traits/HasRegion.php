<?php

namespace App\Models\Traits;

use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasRegion
{
    public function province(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
