<?php

namespace App\Models\Mall;

use App\Contracts\Orderable;
use App\Enums\Mall\DeductStockType;
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
            'coupon_discount' => 'decimal:2',
            'qty' => 'integer',
            'shipped_qty' => 'integer',
            'deduct_stock_type' => DeductStockType::class,
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
     * @return string 小计金额（两位小数字符串）
     */
    public function getSubTotalAttribute(): string
    {
        return bcmul((string) $this->qty, (string) $this->price, 2);
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
     * 获取可订购主体（含软删）
     *
     * 商品被软删后默认关联会返回 null，导致库存扣减/回补被静默跳过，
     * 这里回退到 withTrashed 查询，保证历史订单仍能正确操作库存。
     *
     * @return Orderable|null 可订购主体，物理删除时返回 null
     */
    public function resolveOrderable(): ?Orderable
    {
        if ($this->orderable) {
            return $this->orderable;
        }

        if ($this->orderable_type === Sku::class) {
            return Sku::withTrashed()->find($this->orderable_id);
        }

        return null;
    }

    /**
     * 获取已退款数量
     *
     * 只统计有效退款（进行中或已完成）；软删的退款单同样计入，避免删除退款单后额度复原。
     *
     * @return int 已退款数量
     */
    public function getRefundedQtyAttribute(): int
    {
        if ($this->relationLoaded('refundItems')) {
            $refundedQty = $this->loadedRefundedQty();
        } else {
            $refundedQty = (int) $this->refundItems()
                ->whereHas('refund', fn ($q) => $q->withTrashed()->whereIn('status', RefundStatus::effectiveCases()))
                ->sum('qty');
        }

        return max(0, $refundedQty);
    }

    /**
     * 获取最大可退款数量
     *
     * 剔除有效退款（进行中或已完成）对应的数量；已拒绝/已取消/失败不占用数量。
     *
     * @return int 最大可退款数量
     */
    public function getMaxRefundCounts(): int
    {
        return max(0, (int) $this->qty - $this->getRefundedQtyAttribute());
    }

    /**
     * 获取实际已发数量
     *
     * 兼容历史数据：明细已绑定发货记录但 shipped_qty 仍为 0 时，按整行已发处理。
     *
     * @return int 实际已发数量
     */
    public function resolveShippedQty(): int
    {
        $shippedQty = (int) $this->shipped_qty;

        if ($shippedQty === 0 && $this->order_shipping_id !== null) {
            return $this->getMaxRefundCounts();
        }

        return $shippedQty;
    }

    /**
     * 获取待发货数量
     *
     * 可发数量 = 购买数量 - 已退数量 - 已发数量，用于分拣/发货与发货完成判定。
     *
     * @return int 待发货数量
     */
    public function getShippableQtyAttribute(): int
    {
        return max(0, $this->getMaxRefundCounts() - $this->resolveShippedQty());
    }

    /**
     * 已加载退款明细对应的已退数量
     *
     * 调用方已 eager load `refundItems` 时避免逐条查询，并补齐退款单关联用于状态判断。
     */
    private function loadedRefundedQty(): int
    {
        $this->refundItems->loadMissing(['refund' => fn ($query) => $query->withTrashed()]);

        return (int) $this->refundItems
            ->filter(fn ($item): bool => $item->refund
                && in_array($item->refund->status, RefundStatus::effectiveCases(), true))
            ->sum('qty');
    }
}
