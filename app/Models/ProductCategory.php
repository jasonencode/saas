<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 商品分类关联模型
 */
class ProductCategory extends Pivot
{
    /**
     * 关联商品
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 关联分类
     *
     * @return void
     */
    public function category(): void
    {
        $this->belongsTo(Category::class);
    }
}
