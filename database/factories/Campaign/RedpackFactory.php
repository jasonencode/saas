<?php

namespace Database\Factories\Campaign;

use App\Models\Campaign\Redpack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redpack>
 */
class RedpackFactory extends Factory
{
    protected $model = Redpack::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->word().'红包活动',
            'description' => $this->faker->sentence(),
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => true,
        ];
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
     * 已过期
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => now()->subMonth(),
            'end_at' => now()->subDay(),
        ]);
    }
}
