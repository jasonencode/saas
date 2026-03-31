<?php

namespace App\Models;

use App\Contracts\ShouldPayment;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\OrderScopes;
use App\Policies\OrderPolicy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 订单模型
 *
 * @property OrderStatus $status
 * @property Carbon $expired_at
 * @property Carbon $paid_at
 * @property Carbon $signed_at
 * @property int $products_count
 * @property int $skus_count
 * @property int $skus_quantities
 * @property float $total_amount
 */
#[Unguarded]
#[UsePolicy(OrderPolicy::class)]
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

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (Order $order) {
            $order->expired_at = Carbon::now()->addMinutes((int) config('custom.mall.order_expired_minutes'));
        });
    }

    /**
     * 获取路由键名
     *
     * @return string
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
     * 售后记录
     *
     * @return HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * 物流信息
     *
     * @return HasMany
     */
    public function shippings(): HasMany
    {
        return $this->hasMany(OrderShipping::class);
    }

    /**
     * 订单地址，创建订单的时候，留存完整的地址信息，以防地址修改后，订单显示的地址不一致
     *
     * @return HasOne
     */
    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    /**
     * 订单日志
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    /**
     * 关联支付单
     *
     * @return MorphMany
     */
    public function paymentOrders(): MorphMany
    {
        return $this->morphMany(PaymentOrder::class, 'paymentable');
    }

    /**
     * 支付单展示时，显示的标题
     *
     * @return string
     */
    public function getTitleAttribute(): string
    {
        return sprintf('%s%s', '[商城订单]:', $this->no);
    }

    /**
     * 获取总金额
     */
    public function getTotalAmount(): float
    {
        return (float) bcadd($this->amount, $this->freight, 2);
    }

    /**
     * 商品种类数（不同 product_id 的数量）
     *
     * @return int
     */
    public function getProductsCountAttribute(): int
    {
        return $this->items->pluck('product_id')->unique()->count();
    }

    /**
     * 货品种类数（订单明细条目数，即 SKU 种类数）
     *
     * @return int
     */
    public function getSkusCountAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * 商品总数量（所有明细 qty 之和）
     *
     * @return int
     */
    public function getSkusQuantitiesAttribute(): int
    {
        return (int) $this->items->sum('qty');
    }

    /**
     * 获取订单金额，主要是做展示用的
     *
     * @return float
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->getTotalAmount();
    }
}
