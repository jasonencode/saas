<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Models\Finance\RechargeOrder;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RechargeOrder>
 */
class RechargeOrderFactory extends Factory
{
    protected $model = RechargeOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'user_id' => User::factory(),
            'type' => RechargeOrderType::Balance,
            'gateway' => PaymentGateway::Manual,
            'status' => RechargeOrderStatus::Pending,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'received_amount' => $this->faker->randomFloat(2, 10, 1000),
            'paid_at' => null,
            'completed_at' => null,
            'expired_at' => now()->addHour(),
        ];
    }

    /**
     * 已支付
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RechargeOrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * 已完成
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RechargeOrderStatus::Completed,
            'paid_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }
}
