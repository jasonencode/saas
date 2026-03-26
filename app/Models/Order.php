<?php

namespace App\Models;

use App\Contracts\ShouldPayment;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\OrderScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

/**
 * 订单模型
 *
 * @property OrderStatus $status
 * @property Carbon $expired_at
 * @property Carbon $paid_at
 * @property Carbon $signed_at
 */
#[Unguarded]
class Order extends Model implements ShouldPayment
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        BelongsToUser,
        OrderScopes,
        SoftDeletes;

    protected $casts = [
        'amount' => 'decimal:2',
        'freight' => 'decimal:2',
        'status' => OrderStatus::class,
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => OrderCreated::class,
    ];

    /**
     * 启动方法
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (Order $order) {
            $order->expired_at = Carbon::now()->addMinutes((int) config('custom.mall.order_expired_minutes'));
        });
    }

    /**
     * 获取路由键名
     */
    public function getRouteKeyName(): string
    {
        return 'no';
    }

    /**
     * 订单明细
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 退款记录
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * 物流信息
     */
    public function expresses(): HasMany
    {
        return $this->hasMany(OrderExpress::class);
    }

    /**
     * 订单地址，创建订单的时候，留存完整的地址信息，以防地址修改后，订单显示的地址不一致
     */
    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    /**
     * 订单日志
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    /**
     * 获取总金额
     */
    public function getTotalAmountAttribute(): string
    {
        return bcadd($this->amount, $this->freight, 2);
    }

    /**
     * 支付成功处理
     */
    public function paid(PaymentOrder $order): bool
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new RuntimeException('订单状态不可支付');
        }
        $this->status = OrderStatus::Paid;
        $this->paid_at = $order->paid_at;

        return $this->save();
    }

    public function paymentOrders(): MorphMany
    {
        return $this->morphMany(PaymentOrder::class, 'paymentable');
    }

    public function getTitleAttribute(): string
    {
        return sprintf('%s%s', '[商城订单]:', $this->no);
    }

    /**
     * 商品种类数（不同 product_id 的数量）
     */
    public function getProductsCountAttribute(): int
    {
        return $this->items->pluck('product_id')->unique()->count();
    }

    /**
     * 货品种类数（订单明细条目数，即 SKU 种类数）
     */
    public function getSkusCountAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * 商品总数量（所有明细 qty 之和）
     */
    public function getSkusQuantitiesAttribute(): int
    {
        return (int) $this->items->sum('qty');
    }

    public function getTotalAmount(): string
    {
        return bcadd($this->amount, $this->freight, 2);
    }

    public function canPay(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function canRefund(): bool
    {
        return in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Delivered,
            OrderStatus::Signed,
        ], true);
    }
}
