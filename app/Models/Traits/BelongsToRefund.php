<?php

namespace App\Models\Traits;

use App\Models\Mall\Refund;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 退款关联特征
 *
 * @property int $refund_id
 */
trait BelongsToRefund
{
    /**
     * 关联退款
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class)->withTrashed();
    }

    /**
     * 设置退款属性
     */
    public function setRefundAttribute(Refund $refund): void
    {
        $this->attributes['refund_id'] = $refund->getKey();
    }
}
