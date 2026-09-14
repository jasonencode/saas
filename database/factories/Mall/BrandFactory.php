<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'cover' => null,
            'status' => true,
            'sort' => 0,
            'ext' => null,
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
