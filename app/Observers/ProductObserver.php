<?php

namespace App\Observers;

use App\Models\Mall\Brand;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;

class ProductObserver
{
    public function updated(Product $product): void
    {
        ProductCategory::flushCache();
        Brand::flushCache();
    }

    public function deleted(Product $product): void
    {
        ProductCategory::flushCache();
        Brand::flushCache();
    }
}
