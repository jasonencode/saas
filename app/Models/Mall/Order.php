<?php

namespace App\Models\Mall;

use App\Contracts\ShouldSettlement;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Finance\InvoiceApplication;
use App\Models\Finance\InvoiceApplicationOrder;
use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\OrderScopes;
use App\Models\Traits\Searchable;
use App\Policies\Mall\OrderPolicy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 订单模型
 *
 * @property OrderStatus $status
 * @property Carbon $expired_at
 * @property Carbon $paid_at
 * @property Carbon $signed_at
 * @property int $items_quantity
 * @property float $total_amount
 */
#[Unguarded]
#[UsePolicy(OrderPolicy::class)]
class Order extends Model implements ShouldSettlement
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        BelongsToUser,
        OrderScopes,
        Searchable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'freight' => 'decimal:2',
            'status' => OrderStatus::class,
            'fulfillment_type' => FulfillmentType::class,
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
            'signed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

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
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 售后记录
     *
     * @return HasMany<Refund>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * 物流信息
     *
     * @return HasMany<OrderShipping>
     */
    public function shippings(): HasMany
    {
        return $this->hasMany(OrderShipping::class);
    }

    /**
     * 自提点（仅门店自提订单）
     *
     * @return BelongsTo<PickupPoint>
     */
    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(PickupPoint::class);
    }

    /**
     * 核销人
     *
     * @return BelongsTo
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'verified_by');
    }

    /**
     * 订单地址，创建订单的时候，留存完整的地址信息，以防地址修改后，订单显示的地址不一致
     *
     * @return HasOne<OrderAddress>
     */
    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    /**
     * 订单日志
     *
     * @return HasMany<OrderLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    /**
     * 支付单展示时，显示的标题
     */
    public function getTitleAttribute(): string
    {
        return sprintf('%s%s', '[商城订单]:', $this->no);
    }

    /**
     * 结算展示标题
     */
    public function getSettlementTitleAttribute(): string
    {
        return $this->title;
    }

    /**
     * 商品总数量（所有明细 qty 之和）
     */
    public function getItemsQuantityAttribute(): int
    {
        return (int) $this->items->sum('qty');
    }

    /**
     * 获取订单金额，主要是做展示用的
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->getTotalAmount();
    }

    /**
     * 获取总金额
     */
    public function getTotalAmount(): float
    {
        return (float) bcadd($this->amount, $this->freight, 2);
    }

    /**
     * 关联发票申请
     *
     * @return BelongsToMany<InvoiceApplication>
     */
    public function invoiceApplications(): BelongsToMany
    {
        return $this->belongsToMany(InvoiceApplication::class, 'invoice_application_order')
            ->using(InvoiceApplicationOrder::class)
            ->withTimestamps();
    }
}
