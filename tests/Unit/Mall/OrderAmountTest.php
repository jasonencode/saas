<?php

namespace Tests\Unit\Mall;

use App\Models\Mall\CartItem;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use Tests\TestCase;

class OrderAmountTest extends TestCase
{
    public function test_order_total_amount_returns_decimal_string(): void
    {
        $order = new Order([
            'amount' => '12.30',
            'freight' => '0.70',
        ]);

        $this->assertSame('13.00', $order->getTotalAmount());
        $this->assertSame('13.00', $order->total_amount);
    }

    public function test_order_item_sub_total_returns_decimal_string(): void
    {
        $orderItem = new OrderItem([
            'qty' => 3,
            'price' => '4.20',
        ]);

        $this->assertSame('12.60', $orderItem->sub_total);
    }

    public function test_cart_item_sub_total_returns_decimal_string(): void
    {
        $cartItem = new CartItem([
            'qty' => 3,
            'price_at_add' => '4.20',
        ]);

        $this->assertSame('12.60', $cartItem->sub_total);
    }
}
