<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 微信支付配置模型
 */
#[Unguarded]
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
}
