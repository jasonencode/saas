<?php

namespace Database\Factories\System;

use App\Models\System\BlackList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlackList>
 */
class BlackListFactory extends Factory
{
    protected $model = BlackList::class;

    public function definition(): array
    {
        return [
            'ip' => $this->faker->ipv4(),
            'remark' => $this->faker->sentence(),
        ];
    }

    /**
     * CIDR 范围黑名单
     */
    public function cidr(string $cidr): static
    {
        return $this->state(fn (array $attributes) => [
            'ip' => $cidr,
        ]);
    }
}
