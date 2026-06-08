<?php

namespace Database\Factories\System;

use App\Enums\System\AdminType;
use App\Models\System\Administrator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Administrator>
 */
class AdministratorFactory extends Factory
{
    protected $model = Administrator::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'type' => AdminType::Admin,
            'username' => $this->faker->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'name' => $this->faker->name(),
            'avatar' => null,
        ];
    }

    /**
     * 超级管理员
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => 1,
        ]);
    }

    /**
     * 租户类型管理员
     */
    public function tenantAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AdminType::Tenant,
        ]);
    }
}
