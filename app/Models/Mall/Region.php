<?php

namespace App\Models\Mall;

use App\Enums\Mall\RegionLevel;
use App\Models\Model;
use App\Models\Traits\HasSortable;
use App\Policies\Mall\RegionPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UsePolicy(RegionPolicy::class)]
class Region extends Model
{
    use HasSortable;

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

    /**
     * 下级地区
     *
     * @return HasMany<Region>
     */
    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }
}
