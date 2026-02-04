<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\MorphToUser;
use App\Models\Traits\BelongsToRefund;

class RefundLog extends Model
{
    use BelongsToRefund,
        MorphToUser;

    const null UPDATED_AT = null;

    protected $table = 'mall_refund_logs';

    protected $casts = [
        'context' => 'json',
    ];
}
