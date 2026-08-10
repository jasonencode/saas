<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\HasRegion;
use App\Policies\Mall\OrderShippingPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(OrderShippingPolicy::class)]
class OrderShipping extends Model
{
    use BelongsToOrder,
        HasRegion,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'delivery_at' => 'datetime',
            'sign_at' => 'datetime',
        ];
    }

    /**
     * 关联快递公司
     *
     * @return BelongsTo<Express>
     */
    public function express(): BelongsTo
    {
        return $this->belongsTo(Express::class);
    }

    /**
     * 包含的商品
     *
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 设置地址信息（镜像）
     *
     * @param  OrderAddress  $address  订单地址
     */
    public function setAddress(OrderAddress $address): void
    {
        $this->attributes['name'] = $address->name;
        $this->attributes['mobile'] = $address->mobile;
        $this->attributes['province_id'] = $address->province_id;
        $this->attributes['city_id'] = $address->city_id;
        $this->attributes['district_id'] = $address->district_id;
        $this->attributes['address'] = $address->address;

        $this->save();
    }
}
