<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderExpress extends Model
{
    use BelongsToOrder;

    protected $casts = [
        'delivery_at' => 'timestamp',
        'sign_at' => 'timestamp',
    ];

    public function express(): BelongsTo
    {
        return $this->belongsTo(Express::class);
    }
}
