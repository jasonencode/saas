<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\HasRegion;
use App\Models\User\Address;
use App\Policies\Mall\OrderAddressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Unguarded]
#[UsePolicy(OrderAddressPolicy::class)]
class OrderAddress extends Model
{
    use BelongsToOrder,
        HasRegion;

    /**
     * 从地址模型快速填充收货地址
     *
     * @param  Address  $address  收货地址
     */
    public function fillFromAddress(Address $address): void
    {
        $this->address_id = $address->getKey();
        $this->name = $address->name;
        $this->mobile = $address->mobile;
        $this->province_id = $address->province_id;
        $this->city_id = $address->city_id;
        $this->district_id = $address->district_id;
        $this->address = $address->address;
    }
}
