<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShipping>
 */
class OrderShippingFactory extends Factory
{
    protected $model = OrderShipping::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'express_id' => null,
            'express_no' => strtoupper(bin2hex(random_bytes(5))),
            'name' => $this->faker->name(),
            'mobile' => $this->faker->phoneNumber(),
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'address' => $this->faker->address(),
            'delivery_at' => null,
            'sign_at' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'delivery_at' => now()->subDay(),
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn () => [
            'delivery_at' => now()->subDays(2),
            'sign_at' => now(),
        ]);
    }
}
