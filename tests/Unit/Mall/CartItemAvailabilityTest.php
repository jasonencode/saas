<?php

namespace Tests\Unit\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\CartItem;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use Tests\TestCase;

class CartItemAvailabilityTest extends TestCase
{
    public function test_cart_item_is_not_available_when_product_is_down(): void
    {
        $cartItem = new CartItem([
            'qty' => 1,
        ]);

        $cartItem->setRelation('product', new Product([
            'status' => ProductStatus::Down,
        ]));

        $cartItem->setRelation('sku', new Sku([
            'stock' => 10,
        ]));

        $this->assertFalse($cartItem->isAvailable());
    }

    public function test_cart_item_is_available_when_product_is_up_and_stock_is_enough(): void
    {
        $cartItem = new CartItem([
            'qty' => 2,
        ]);

        $cartItem->setRelation('product', new Product([
            'status' => ProductStatus::Up,
        ]));

        $cartItem->setRelation('sku', new Sku([
            'stock' => 10,
        ]));

        $this->assertTrue($cartItem->isAvailable());
    }
}
