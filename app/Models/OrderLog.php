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

    /**
     * 更新时间
     */
    const null UPDATED_AT = null;

    /**
     * 属性转换
     */
    public function casts(): array
    {
        return [
            'context' => 'json',
        ];
    }
}
