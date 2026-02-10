<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentOrder extends Model
{
    use AutoCreateOrderNo,
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

    public function getRouteKeyName(): string
    {
        return 'no';
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
