<?php

namespace Database\Factories\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Campaign\RedpackCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RedpackCode>
 */
class RedpackCodeFactory extends Factory
{
    protected $model = RedpackCode::class;

    public function definition(): array
    {
        return [
            'redpack_id' => null,
            'amount' => $this->faker->randomFloat(2, 0.01, 200),
            'status' => RedpackCodeStatus::Active,
        ];
    }

    /**
     * 已领取
     */
    public function claimed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RedpackCodeStatus::Claimed,
            'claimed_at' => now(),
        ]);
    }

    /**
     * 已禁用
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RedpackCodeStatus::Disabled,
        ]);
    }
}
