<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\WechatPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 微信配置模型
 */
#[Unguarded]
#[UsePolicy(WechatPolicy::class)]
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

    public function getConfig(): array
    {
        return [

        ];
    }
}
