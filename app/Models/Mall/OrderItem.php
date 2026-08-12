<?php

namespace App\Models\Mall;

use App\Contracts\Orderable;
use App\Enums\Mall\RefundStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Policies\Mall\OrderItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
#[UsePolicy(OrderItemPolicy::class)]
#[WithoutTimestamps]
class OrderItem extends Model
{
    use BelongsToOrder;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * 可订购主体多态关联
     *
     * @return MorphTo<Model&Orderable>
     */
    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 设置可订购主体
     *
     * @param  Model  $model  可订购主体模型
     */
    public function setOrderableAttribute(Model $model): void
    {
        $this->attributes['orderable_type'] = $model->getMorphClass();
        $this->attributes['orderable_id'] = $model->getKey();
    }

    /**
     * 小计金额
     *
     * @return float 小计金额
     */
    public function getSubTotalAttribute(): float
    {
        return (float) bcmul($this->qty, $this->price, 2);
    }

    /**
     * 关联退款明细
     *
     * @return HasMany<RefundItem>
     */
    public function refundItems(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * 关联物流
     *
     * @return BelongsTo<OrderShipping>
     */
    public function orderShipping(): BelongsTo
    {
        return $this->belongsTo(OrderShipping::class);
    }

    /**
     * 获取最大可退款数量
     *
     * @return int 最大可退款数量
     */
    public function getMaxRefundCounts(): int
    {
        $activeStatuses = [
            RefundStatus::Pending,
            RefundStatus::WaitingReturn,
            RefundStatus::Shipping,
            RefundStatus::Received,
            RefundStatus::Processing,
        ];

        $refundedQty = $this->refundItems()
            ->whereHas('refund', fn ($q) => $q->whereIn('status', $activeStatuses))
            ->sum('qty');

        return $this->qty - $refundedQty;
    }
}
