<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order;

trait BelongsToOrder
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
