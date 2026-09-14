<?php

namespace Database\Factories\User;

use App\Models\User\Address;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'mobile' => $this->faker->phoneNumber(),
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'address' => $this->faker->address(),
            'is_default' => false,
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
}
