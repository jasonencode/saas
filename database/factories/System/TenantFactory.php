<?php

namespace Database\Factories\System;

use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.Str::random(4)),
            'expired_at' => now()->addYear(),
            'avatar' => null,
            'app_key' => Str::random(32),
            'app_secret' => Str::random(32),
            'status' => true,
            'config' => null,
        ];
    }

    /**
     * 过期租户
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired_at' => now()->subDay(),
        ]);
    }

    /**
     * 禁用租户
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
