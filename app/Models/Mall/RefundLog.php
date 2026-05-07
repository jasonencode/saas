<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use App\Models\Traits\MorphToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

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
