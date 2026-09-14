<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentOrder>
 */
class PaymentOrderFactory extends Factory
{
    protected $model = PaymentOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'user_id' => User::factory(),
            'paymentable_type' => null,
            'paymentable_id' => null,
            'gateway' => PaymentGateway::Manual,
            'status' => PaymentStatus::Pending,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'paid_at' => null,
            'expired_at' => now()->addHour(),
            'credential' => null,
        ];
    }

    /**
     * 已支付
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * 已取消
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Canceled,
        ]);
    }

    /**
     * 支付失败
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }
}
