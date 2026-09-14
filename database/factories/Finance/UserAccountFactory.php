<?php

namespace Database\Factories\Finance;

use App\Models\Finance\UserAccount;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAccount>
 */
class UserAccountFactory extends Factory
{
    protected $model = UserAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 0.00,
            'frozen_balance' => 0.00,
            'points' => 0.00,
            'frozen_points' => 0.00,
            'payment_password' => null,
        ];
    }

    /**
     * 有余额
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }

    /**
     * 有积分
     */
    public function withPoints(float $points): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => $points,
        ]);
    }
}
