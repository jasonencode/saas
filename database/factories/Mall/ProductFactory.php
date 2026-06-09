<?php

namespace Database\Factories\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'cover' => null,
            'pictures' => [],
            'category_id' => null,
            'brand_id' => null,
            'can_cart' => true,
            'status' => ProductStatus::Up,
            'sort' => 0,
            'views' => 0,
        ];
    }

    /**
     * 已上架
     */
    public function up(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Up,
        ]);
    }

    /**
     * 已下架
     */
    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Down,
        ]);
    }

    /**
     * 待审核
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Pending,
        ]);
    }
}
