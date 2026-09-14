<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\UserAccountLog;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAccountLog>
 */
class UserAccountLogFactory extends Factory
{
    protected $model = UserAccountLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => UserAccountLogType::Recharge,
            'asset' => AccountAssetType::Balance,
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'before' => 0.00,
            'after' => $this->faker->randomFloat(2, 10, 100),
            'source_type' => null,
            'source_id' => null,
            'extra' => null,
            'remark' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * 充值
     */
    public function recharge(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserAccountLogType::Recharge,
        ]);
    }

    /**
     * 消费
     */
    public function consume(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserAccountLogType::Consume,
        ]);
    }

    /**
     * 退款
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserAccountLogType::Refund,
        ]);
    }

    /**
     * 奖励
     */
    public function reward(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserAccountLogType::Reward,
        ]);
    }
}
