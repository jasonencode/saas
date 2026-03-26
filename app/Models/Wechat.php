<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 微信配置模型
 */
#[Unguarded]
class Wechat extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'is_connected' => 'boolean',
    ];

    /**
     * 关联支付配置
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(WechatPayment::class);
    }
}
