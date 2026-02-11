<?php

namespace App\Models\Traits;

use App\Models\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 订单关联特征
 *
 * @module 商城
 */
trait BelongsToOrder
{
    /**
     * 所属订单
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
