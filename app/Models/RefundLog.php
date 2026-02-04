<?php

namespace App\Models;

use App\Models\Traits\BelongsToRefund;
use App\Models\Traits\MorphToUser;

class RefundLog extends Model
{
    use BelongsToRefund,
        MorphToUser;

    const null UPDATED_AT = null;

    protected $casts = [
        'context' => 'json',
    ];
}
