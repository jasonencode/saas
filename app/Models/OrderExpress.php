<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\HasRegion;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 订单物流模型
 */
#[Unguarded]
class OrderExpress extends Model
{
    use BelongsToOrder,
        HasRegion,
        SoftDeletes;

    protected $casts = [
        'delivery_at' => 'timestamp',
        'sign_at' => 'timestamp',
    ];

    /**
     * 关联快递公司
     */
    public function express(): BelongsTo
    {
        return $this->belongsTo(Express::class);
    }

    /**
     * 包含的商品
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 设置地址信息（镜像）
     */
    public function setAddress(OrderAddress $address): void
    {
        $this->name = $address->name;
        $this->mobile = $address->mobile;
        $this->province_id = $address->province_id;
        $this->city_id = $address->city_id;
        $this->district_id = $address->district_id;
        $this->address = $address->address;
    }
}
