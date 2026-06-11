<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Sku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sku>
 */
class SkuFactory extends Factory
{
    protected $model = Sku::class;

    public function definition(): array
    {
        return [
            'product_id' => null,
            'name' => $this->faker->randomElement(['红色/S', '蓝色/M', '黑色/L']),
            'code' => $this->faker->ean13(),
            'origin_price' => $this->faker->randomFloat(2, 10, 1000),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock' => $this->faker->numberBetween(0, 100),
            'sale' => 0,
        ];
    }
}
