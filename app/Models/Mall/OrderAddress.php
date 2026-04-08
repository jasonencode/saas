<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\HasRegion;
use App\Models\User\Address;
use App\Policies\OrderAddressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 订单地址模型
 */
#[Unguarded]
#[UsePolicy(OrderAddressPolicy::class)]
class OrderAddress extends Model
{
    use BelongsToOrder,
        HasRegion;

    /**
     * 快速通过属性设置地址
     *
     * @param  Address  $address
     * @return void
     */
    public function setAddressAttribute(Address $address): void
    {
        $this->attributes['address_id'] = $address->getKey();
        $this->attributes['name'] = $address->name;
        $this->attributes['mobile'] = $address->mobile;
        $this->attributes['province_id'] = $address->province_id;
        $this->attributes['city_id'] = $address->city_id;
        $this->attributes['district_id'] = $address->district_id;
        $this->attributes['address'] = $address->address;
    }
}
