<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\User\Identity;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class ProductDiscount extends Model
{
    protected function casts(): array
    {
        return [
            'percent' => 'integer',
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

    /**
     * 关联身份
     *
     * @return BelongsTo<Identity>
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }
}
