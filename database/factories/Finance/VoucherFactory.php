<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Plan;
use App\Models\Finance\Voucher;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'target_type' => Order::class,
            'target_id' => Order::factory(),
            'status' => VoucherStatus::Pending,
            'scheduled_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * 结算成功
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Success,
            'completed_at' => now(),
        ]);
    }

    /**
     * 结算失败
     */
    public function failure(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Failure,
        ]);
    }

    /**
     * 执行中
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Processing,
        ]);
    }
}
