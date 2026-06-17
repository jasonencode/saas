<?php

namespace Database\Factories\Campaign;

use App\Enums\Campaign\LotteryDrawMode;
use App\Models\Campaign\Lottery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lottery>
 */
class LotteryFactory extends Factory
{
    protected $model = Lottery::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word().'抽奖活动',
            'draw_mode' => LotteryDrawMode::Free,
            'free_draws_per_day' => 3,
            'max_draws_per_user' => null,
            'points_per_draw' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(30),
            'status' => true,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDay(),
        ]);
    }

    public function points(): static
    {
        return $this->state(fn () => [
            'draw_mode' => LotteryDrawMode::Points,
            'points_per_draw' => 10,
        ]);
    }
}
