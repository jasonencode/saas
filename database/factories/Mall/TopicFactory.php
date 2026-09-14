<?php

namespace Database\Factories\Mall;

use App\Models\Mall\Topic;
use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true).'专题';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
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
