<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasRegion;
use App\Services\SensitiveService;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户地址模型
 *
 * @module 商城
 */
class Address extends Model
{
    use HasRegion,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::saved(static function ($model) {
            if ($model->is_default && $model->id) {
                Address::where('id', '<>', $model->id)
                    ->where('user_id', $model->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * 设置默认地址
     *
     * @return bool
     */
    public function setDefault(): bool
    {
        $this->is_default = true;

        return $this->save();
    }

    /**
     * 设置区县，同时设置省份和城市
     *
     * @param  Region  $region
     * @return void
     */
    protected function setDistrictAttribute(Region $region): void
    {
        $this->attributes['province_id'] = $region->parent->parent->id;
        $this->attributes['city_id'] = $region->parent->id;
        $this->attributes['district_id'] = $region->id;
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

    /**
     * 设置姓名，过滤敏感字符
     *
     * @param  string  $value
     * @return void
     */
    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = resolve(SensitiveService::class)->filter($value);
    }

    /**
     * 设置详细地址，过滤敏感字符
     *
     * @param  string  $value
     * @return void
     */
    protected function setAddressAttribute(string $value): void
    {
        $this->attributes['address'] = resolve(SensitiveService::class)->filter($value);
    }
}
