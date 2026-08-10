<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\MorphToUser;
use App\Policies\Mall\ProductLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UsePolicy(ProductLogPolicy::class)]
class ProductLog extends Model
{
    use MorphToUser;

    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'records' => 'json',
        ];
    }

    /**
     * 关联商品
     *
     * @return BelongsTo<Product>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
