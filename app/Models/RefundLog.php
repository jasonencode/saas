<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;
use App\Models\Traits\MorphToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * 退款日志模型
 */
#[Unguarded]
class RefundLog extends Model
{
    use BelongsToRefund,
        MorphToUser;

    const null UPDATED_AT = null;

    protected $casts = [
        'context' => 'json',
    ];
}
