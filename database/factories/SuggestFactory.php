<?php

namespace Database\Factories;

use App\Enums\Content\SuggestStatus;
use App\Enums\Content\SuggestType;
use App\Models\Content\Suggest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suggest>
 */
class SuggestFactory extends Factory
{
    protected $model = Suggest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(SuggestType::cases()),
            'contact' => $this->faker->optional()->phoneNumber,
            'status' => SuggestStatus::Pending,
        ];
    }
}
