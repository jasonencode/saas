<?php

namespace App\Models;

use App\Enums\RegionLevel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 地区模型
 */
class Region extends Model
{
    protected $casts = [
        'level' => RegionLevel::class,
    ];

    /**
     * 上级地区
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__);
    }
}
