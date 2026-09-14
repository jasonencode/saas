<?php

namespace Database\Factories\Content;

use App\Models\Content\Suggest;
use App\Models\Content\SuggestMessage;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuggestMessage>
 */
class SuggestMessageFactory extends Factory
{
    protected $model = SuggestMessage::class;

    public function definition(): array
    {
        return [
            'suggest_id' => Suggest::factory(),
            'sender_type' => User::class,
            'sender_id' => User::factory(),
            'content' => $this->faker->text(100),
        ];
    }
}
