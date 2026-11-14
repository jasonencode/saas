<?php

namespace App\Observers;

use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;

class ProductObserver
{
    public function updated(Product $product): void
    {
        (new ProductCategory)->flushCache();
    }

    public function deleted(Product $product): void
    {
        (new ProductCategory)->flushCache();
    }
}
