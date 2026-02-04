<?php

namespace App\Models;

use App\Models\Address;
use App\Models\Model;
use App\Models\Traits\HasRegion;
use App\Models\Traits\BelongsToOrder;

class OrderAddress extends Model
{
    use BelongsToOrder,
        HasRegion;

    protected $table = 'mall_order_addresses';

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

    public function getFullAddressAttribute(): string
    {
        return $this->province->name.$this->city->name.$this->district->name.$this->address;
    }
}
