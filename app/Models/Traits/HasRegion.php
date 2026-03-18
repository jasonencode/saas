<?php

namespace App\Models\Traits;

use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 地区关联特征
 */
trait HasRegion
{
    /**
     * 关联省份
     *
     * @return BelongsTo
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * 关联城市
     *
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * 关联区县
     *
     * @return BelongsTo
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
