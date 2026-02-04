<?php

namespace App\Models;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToOrder;

class OrderExpress extends Model
{
    use BelongsToOrder;

    protected $table = 'mall_order_expresses';

    protected $casts = [
        'delivery_at' => 'timestamp',
        'sign_at' => 'timestamp',
    ];

    public function express(): BelongsTo
    {
        return $this->belongsTo(Express::class);
    }
}
