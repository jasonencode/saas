<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\WithdrawGateway;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\WithdrawOrder;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawOrder>
 */
class WithdrawOrderFactory extends Factory
{
    protected $model = WithdrawOrder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gateway' => WithdrawGateway::Manual,
            'status' => WithdrawOrderStatus::Pending,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'fee' => 0,
            'actual_amount' => $this->faker->randomFloat(2, 10, 1000),
            'account_info' => ['name' => $this->faker->name(), 'account' => $this->faker->bankAccountNumber()],
            'reviewed_at' => null,
            'reviewer_id' => null,
            'paid_at' => null,
            'remark' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * 已审核
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WithdrawOrderStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * 已打款
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WithdrawOrderStatus::Completed,
            'reviewed_at' => now()->subDay(),
            'paid_at' => now(),
        ]);
    }

    /**
     * 已拒绝
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WithdrawOrderStatus::Rejected,
            'reviewed_at' => now(),
        ]);
    }
}
