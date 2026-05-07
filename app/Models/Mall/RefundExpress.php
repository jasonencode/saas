<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

#[Unguarded]
class RefundExpress extends Model
{
    use BelongsToRefund;

    protected $casts = [
        'deliver_at' => 'datetime',
        'receive_at' => 'datetime',
    ];
}
