<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\WechatPaymentPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 微信支付配置模型
 */
#[Unguarded]
#[UsePolicy(WechatPaymentPolicy::class)]
class WechatPayment extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    /**
     * 关联微信配置
     *
     * @return BelongsTo
     */
    public function wechat(): BelongsTo
    {
        return $this->belongsTo(Wechat::class);
    }

    public function getConfig(): array
    {
        return [];
    }
}
