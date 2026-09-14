<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Plan;
use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->word().'计划',
            'description' => $this->faker->sentence(),
            'status' => true,
            'sort' => 0,
        ];
    }

    /**
     * 禁用状态
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
