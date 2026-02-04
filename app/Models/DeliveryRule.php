<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRule extends Model
{
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
