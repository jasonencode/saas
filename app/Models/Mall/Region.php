<?php

namespace App\Models\Mall;

use App\Enums\Mall\RegionLevel;
use App\Models\Model;
use App\Policies\Mall\RegionPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UsePolicy(RegionPolicy::class)]
class Region extends Model
{
    protected function casts(): array
    {
        return [
            'level' => RegionLevel::class,
        ];
    }

    /**
     * 上级地区
     *
     * @return BelongsTo<Region>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__);
    }
}
