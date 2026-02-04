<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\BelongsToRefund;

class RefundExpress extends Model
{
    use BelongsToRefund;

    protected $table = 'mall_refund_expresses';

    protected $casts = [
        'deliver_at' => 'datetime',
        'receive_at' => 'datetime',
    ];
}
