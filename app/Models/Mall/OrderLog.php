<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\MorphToUser;
use App\Policies\Mall\OrderLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Unguarded]
#[UsePolicy(OrderLogPolicy::class)]
class OrderLog extends Model
{
    use BelongsToOrder,
        MorphToUser;

    const null UPDATED_AT = null;

    public function casts(): array
    {
        return [
            'context' => 'json',
        ];
    }
}
