<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\HasRegion;
use App\Policies\Mall\OrderAddressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Unguarded]
#[UsePolicy(OrderAddressPolicy::class)]
class OrderAddress extends Model
{
    use BelongsToOrder,
        HasRegion;
}
