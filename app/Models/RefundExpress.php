<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;

class RefundExpress extends Model
{
    use BelongsToRefund;

    protected $casts = [
        'deliver_at' => 'datetime',
        'receive_at' => 'datetime',
    ];
}
