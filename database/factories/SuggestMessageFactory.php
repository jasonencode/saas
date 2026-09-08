<?php

namespace Database\Factories;

use App\Models\Content\SuggestMessage;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuggestMessage>
 */
class SuggestMessageFactory extends Factory
{
    protected $model = SuggestMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->text(100),
            'sender_type' => User::class,
            'sender_id' => User::factory(),
        ];
    }
}
