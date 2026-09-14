<?php

namespace Database\Factories\Content;

use App\Enums\Content\SuggestStatus;
use App\Enums\Content\SuggestType;
use App\Models\Content\Suggest;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suggest>
 */
class SuggestFactory extends Factory
{
    protected $model = Suggest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(SuggestType::cases()),
            'contact' => $this->faker->optional()->phoneNumber(),
            'status' => SuggestStatus::Pending,
        ];
    }

    /**
     * 已处理
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SuggestStatus::Processed,
        ]);
    }
}
