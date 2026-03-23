<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\HasRegion;

/**
 * 订单地址模型
 */
class OrderAddress extends Model
{
    use BelongsToOrder,
        HasRegion;

    /**
     * 设置地址属性
     *
     * @param  Address  $address
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
