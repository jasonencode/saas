<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[WithoutTimestamps]
class RefundItem extends Model
{
    use BelongsToRefund;

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * 关联退款
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * 关联订单明细
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
