<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;
use App\Policies\RefundItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 退款明细模型
 */
#[Unguarded]
#[UsePolicy(RefundItemPolicy::class)]
#[WithoutTimestamps]
class RefundItem extends Model
{
    use BelongsToRefund;

    protected $casts = [
        'price' => 'decimal:2',
    ];

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
