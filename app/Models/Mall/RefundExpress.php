<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use App\Policies\Mall\RefundExpressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

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
