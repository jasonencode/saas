<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'order_shipping_id' => null,
            'orderable_type' => null,
            'orderable_id' => null,
            'orderable_name' => $this->faker->words(3, true),
            'qty' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'coupon_discount' => 0,
            'remark' => null,
        ];
    }
}
