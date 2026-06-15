<?php

namespace Database\Factories\Mall;

use App\Enums\Mall\DeliveryType;
use App\Models\Mall\Delivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->word().'运费模板',
            'type' => DeliveryType::Count,
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
            'free_shipping_threshold' => 0,
            'is_default' => false,
            'status' => true,
        ];
    }

    /**
     * 按重量计费
     */
    public function weight(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DeliveryType::Weight,
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);
    }

    /**
     * 按数量计费
     */
    public function countType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DeliveryType::Count,
            'first' => 1,
            'first_fee' => 10.00,
            'additional' => 1,
            'additional_fee' => 5.00,
        ]);
    }

    /**
     * 设为默认
     */
    public function asDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * 已禁用
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * 设置包邮门槛
     */
    public function withFreeShipping(float $threshold): static
    {
        return $this->state(fn (array $attributes) => [
            'free_shipping_threshold' => $threshold,
        ]);
    }
}
