<?php

namespace Database\Factories\Mall;

use App\Models\Mall\ReturnAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnAddress>
 */
class ReturnAddressFactory extends Factory
{
    protected $model = ReturnAddress::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->name(),
            'mobile' => $this->faker->phoneNumber(),
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'address' => $this->faker->address(),
            'is_default' => false,
            'status' => true,
            'sort' => 0,
        ];
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
     * 禁用状态
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
