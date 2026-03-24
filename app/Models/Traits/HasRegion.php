<?php

namespace App\Models\Traits;

use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 地区关联特征
 *
 * @property int $province_id
 * @property int $city_id
 * @property int $district_id
 * @property string $address
 * @property string $full_address
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

    /**
     * 获取完整地址
     *
     * @return string
     */
    protected function getFullAddressAttribute(): string
    {
        return sprintf(
            '%s %s %s %s',
            $this->province->name,
            $this->city->name,
            $this->district->name,
            $this->address
        );
    }
}
