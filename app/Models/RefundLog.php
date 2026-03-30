<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;
use App\Models\Traits\MorphToUser;
use App\Policies\RefundLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 退款日志模型
 */
#[Unguarded]
#[UsePolicy(RefundLogPolicy::class)]
class RefundLog extends Model
{
    use BelongsToRefund,
        MorphToUser;

    const null UPDATED_AT = null;

    protected $casts = [
        'context' => 'json',
    ];
}
