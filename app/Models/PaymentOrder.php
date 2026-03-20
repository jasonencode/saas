<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 支付订单模型
 */
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
     *
     * @return MorphTo
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }
}
