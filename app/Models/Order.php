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
     * 退款记录
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
    public function expresses(): HasMany
    {
        return $this->hasMany(OrderExpress::class);
    }

    /**
     * 订单地址，创建订单的时候，留存完整的地址信息，以防地址修改后，订单显示的地址不一致
     *
     * @return HasOne
     */
    public function address(): hasOne
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
     * 获取总金额
     *
     * @return string
     */
    public function getTotalAmountAttribute(): string
    {
        return bcadd($this->amount, $this->freight, 2);
    }

    /**
     * 支付成功处理
     *
     * @param  PaymentOrder  $order
     * @return bool
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

    public function getTitle(): string
    {
        return sprintf('%s%s', '[商城订单]:', $this->no);
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
