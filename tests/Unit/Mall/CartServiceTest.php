<?php

namespace Tests\Unit\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Cart;
use App\Models\Mall\CartItem;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Services\Mall\CartService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    public function test_validate_cart_items_returns_down_message_for_unavailable_product(): void
    {
        $cartItem = new CartItem([
            'id' => 1,
            'qty' => 1,
        ]);

        $cartItem->setRelation('product', new Product([
            'status' => ProductStatus::Down,
        ]));

        $cartItem->setRelation('sku', new Sku([
            'stock' => 10,
        ]));

        $cart = new Cart;
        $cart->setRelation('items', new Collection([$cartItem]));

        $result = app(CartService::class)->validateCartItems($cart);

        $this->assertTrue($result['has_invalid']);
        $this->assertSame('商品已下架', $result['invalid'][0]['reason']);
    }
}
