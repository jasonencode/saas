<?php

namespace Database\Factories\System;

use App\Models\System\Sensitive;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sensitive>
 */
class SensitiveFactory extends Factory
{
    protected $model = Sensitive::class;

    public function definition(): array
    {
        return [
            'keywords' => $this->faker->word(),
        ];
    }
}
