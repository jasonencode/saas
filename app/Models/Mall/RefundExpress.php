<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use App\Policies\RefundExpressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 退款物流模型
 */
#[Unguarded]
#[UsePolicy(RefundExpressPolicy::class)]
class RefundExpress extends Model
{
    use BelongsToRefund;

    protected $casts = [
        'deliver_at' => 'datetime',
        'receive_at' => 'datetime',
    ];
}
