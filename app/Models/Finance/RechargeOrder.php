<?php

namespace App\Models\Finance;

use App\Contracts\ShouldPayment;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Models\Model;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\Finance\RechargeOrderPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(RechargeOrderPolicy::class)]
class RechargeOrder extends Model implements ShouldPayment
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => RechargeOrderType::class,
            'gateway' => PaymentGateway::class,
            'status' => RechargeOrderStatus::class,
            'amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (self $model) {
            $model->status = RechargeOrderStatus::Pending;
        });
    }

    /**
     * 关联支付单
     *
     * @return MorphMany<PaymentOrder>
     */
    public function paymentOrders(): MorphMany
    {
        return $this->morphMany(PaymentOrder::class, 'paymentable');
    }

    /**
     * 获取支付标题
     */
    public function getTitleAttribute(): string
    {
        return sprintf('%s%s', '[充值订单]:', $this->no);
    }

    /**
     * 获取支付金额
     *
     * @return float 支付金额
     */
    public function getTotalAmount(): float
    {
        return number_format($this->amount, 2, '.', '');
    }
}
