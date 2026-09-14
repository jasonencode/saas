<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Order;
use App\Models\Mall\OrderAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderAddress>
 */
class OrderAddressFactory extends Factory
{
    protected $model = OrderAddress::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'address_id' => null,
            'name' => $this->faker->name(),
            'mobile' => $this->faker->phoneNumber(),
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'address' => $this->faker->address(),
        ];
    }
}
