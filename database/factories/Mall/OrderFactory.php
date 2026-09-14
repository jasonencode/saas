<?php

namespace Database\Factories\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'freight' => 0,
            'coupon_discount' => 0,
            'expired_at' => now()->addHour(),
            'status' => OrderStatus::Pending,
            'fulfillment_type' => FulfillmentType::Mail,
            'pickup_code' => null,
            'pickup_point_id' => null,
            'verified_by' => null,
            'verified_at' => null,
            'remark' => $this->faker->optional()->sentence(),
            'seller_remark' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Pending]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Completed,
            'paid_at' => now()->subDay(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Canceled]);
    }

    public function withPickup(): static
    {
        return $this->state(fn () => [
            'fulfillment_type' => FulfillmentType::Pickup,
            'pickup_code' => strtoupper(bin2hex(random_bytes(3))),
        ]);
    }

    public function virtual(): static
    {
        return $this->state(fn () => [
            'fulfillment_type' => FulfillmentType::Virtual,
            'freight' => 0,
        ]);
    }
}
