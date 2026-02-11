<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;

/**
 * 退款物流模型
 */
class RefundExpress extends Model
{
    use BelongsToRefund;

    protected $casts = [
        'deliver_at' => 'datetime',
        'receive_at' => 'datetime',
    ];
}
