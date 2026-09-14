<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Banner;
use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $this->faker->words(3, true).'轮播图',
            'cover' => null,
            'url' => $this->faker->optional()->url(),
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
