<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRefund>
 */
class PaymentRefundFactory extends Factory
{
    protected $model = PaymentRefund::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'payment_order_id' => PaymentOrder::factory(),
            'created_by_type' => null,
            'created_by_id' => null,
            'source_type' => null,
            'source_id' => null,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'reason' => $this->faker->sentence(),
            'status' => PaymentRefundStatus::Pending,
            'refunded_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ];
    }

    /**
     * 已退款
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRefundStatus::Completed,
            'refunded_at' => now(),
        ]);
    }

    /**
     * 已审批
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRefundStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * 已拒绝
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRefundStatus::Rejected,
            'approved_at' => now(),
        ]);
    }
}
