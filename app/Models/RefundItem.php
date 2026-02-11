<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 退款明细模型
 */
class RefundItem extends Model
{
    use BelongsToRefund;

    public $timestamps = false;

    /**
     * 关联退款
     *
     * @return BelongsTo
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * 关联订单明细
     *
     * @return BelongsTo
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
