<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\MorphToUser;
use App\Models\Traits\BelongsToOrder;

class OrderLog extends Model
{
    use BelongsToOrder,
        MorphToUser;

    const null UPDATED_AT = null;

    protected $table = 'mall_order_logs';

    public function casts(): array
    {
        return [
            'context' => 'json',
        ];
    }
}
