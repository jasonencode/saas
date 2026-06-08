<?php

namespace Database\Factories\System;

use App\Models\System\AdminRole;
use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminRole>
 */
class AdminRoleFactory extends Factory
{
    protected $model = AdminRole::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'is_sys' => false,
        ];
    }

    /**
     * 系统内置角色
     */
    public function systemRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sys' => true,
        ]);
    }
}
