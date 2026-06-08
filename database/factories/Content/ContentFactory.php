<?php

namespace Database\Factories\Content;

use App\Models\Content\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'sub_title' => $this->faker->sentence(3),
            'description' => $this->faker->text(100),
            'cover' => null,
            'author' => $this->faker->name(),
            'source' => $this->faker->domainName(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => true,
            'views' => 0,
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
