<?php

namespace App\Models;

use App\Enums\PaymentRefundStatus;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class PaymentRefund extends Model
{
    use AutoCreateOrderNo,
        BelongsToTenant,
        SoftDeletes;

    protected $casts = [
        'refunded_at' => 'datetime',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'status' => PaymentRefundStatus::class,
    ];

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class);
    }

    public function creator(): MorphTo
    {
        return $this->morphTo('created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'approved_by');
    }
}
