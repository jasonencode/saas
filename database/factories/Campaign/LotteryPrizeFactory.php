<?php

namespace Database\Factories\Campaign;

use App\Enums\Campaign\LotteryPrizeType;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LotteryPrize>
 */
class LotteryPrizeFactory extends Factory
{
    protected $model = LotteryPrize::class;

    public function definition(): array
    {
        return [
            'lottery_id' => Lottery::factory(),
            'name' => $this->faker->word().'奖品',
            'type' => LotteryPrizeType::Coupon,
            'prize_config' => ['coupon_id' => null],
            'weight' => $this->faker->numberBetween(1, 100),
            'total_quantity' => $this->faker->numberBetween(10, 100),
            'remaining_quantity' => $this->faker->numberBetween(5, 50),
            'user_limit' => null,
        ];
    }

    public function none(): static
    {
        return $this->state(fn () => [
            'type' => LotteryPrizeType::None,
            'name' => '谢谢参与',
            'weight' => 50,
            'total_quantity' => 0,
            'remaining_quantity' => 0,
        ]);
    }

    public function physical(): static
    {
        return $this->state(fn () => [
            'type' => LotteryPrizeType::Physical,
            'name' => '实物奖品',
            'prize_config' => ['description' => '精美礼品'],
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(fn () => [
            'remaining_quantity' => 0,
        ]);
    }
}
