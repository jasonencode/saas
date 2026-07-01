<?php

namespace App\Events\Mall;

use App\Models\Mall\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductChanged
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public Product $product,
        public string $action, // created / updated / deleted
    ) {}
}
