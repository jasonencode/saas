<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasRegion;
use App\Models\Traits\HasSortable;
use App\Policies\Mall\PickupPointPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(PickupPointPolicy::class)]
class PickupPoint extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        HasRegion,
        HasSortable,
        SoftDeletes;

    /**
     * 关联商品
     *
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pickup_point_product', 'pickup_point_id', 'product_id')
            ->withPivot('sort')
            ->orderByPivotDesc('sort');
    }
}
