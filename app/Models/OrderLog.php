<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\MorphToUser;
use App\Policies\OrderLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 订单日志模型
 */
#[Unguarded]
#[UsePolicy(OrderLogPolicy::class)]
class OrderLog extends Model
{
    use BelongsToOrder,
        MorphToUser;

    const null UPDATED_AT = null;

    public function casts(): array
    {
        return [
            'context' => 'json',
        ];
    }
}
