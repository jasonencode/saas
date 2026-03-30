<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\PaymentOrderPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 支付订单模型
 */
#[Unguarded]
#[UsePolicy(PaymentOrderPolicy::class)]
class PaymentOrder extends Model
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'extra' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (self $model) {
            $model->status = PaymentStatus::Pending;
        });
    }

    /**
     * 支付关联模型
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 设置支付关联模型
     */
    protected function setPaymentableAttribute(Model $value): void
    {
        $this->attributes['paymentable_type'] = $value->getMorphClass();
        $this->attributes['paymentable_id'] = $value->getKey();
    }

    /**
     * 退款订单
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }
}
