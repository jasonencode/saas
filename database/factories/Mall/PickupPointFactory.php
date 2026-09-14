<?php

namespace Database\Factories\Mall;

use App\Models\Mall\PickupPoint;
use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupPoint>
 */
class PickupPointFactory extends Factory
{
    protected $model = PickupPoint::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->city().'自提点',
            'contact' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'address' => $this->faker->address(),
            'remark' => $this->faker->optional()->sentence(),
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
