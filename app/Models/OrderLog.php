<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\MorphToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * 订单日志模型
 */
#[Unguarded]
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
