<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 订单物流模型
 */
class OrderExpress extends Model
{
    use BelongsToOrder;

    protected $casts = [
        'delivery_at' => 'timestamp',
        'sign_at' => 'timestamp',
    ];

    /**
     * 关联快递公司
     *
     * @return BelongsTo
     */
    public function express(): BelongsTo
    {
        return $this->belongsTo(Express::class);
    }
}
