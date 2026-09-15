<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Express;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Express>
 */
class ExpressFactory extends Factory
{
    protected $model = Express::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['顺丰速运', '中通快递', '圆通速递', '韵达快递', '申通快递', '邮政EMS']),
            'code' => $this->faker->randomElement(['SF', 'ZTO', 'YTO', 'YD', 'STO', 'EMS']),
            'cover' => null,
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
