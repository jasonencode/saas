<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Plan;
use App\Models\Finance\Voucher;
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
            'target_type' => null,
            'target_id' => null,
            'status' => VoucherStatus::Pending,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'remark' => $this->faker->optional()->sentence(),
            'scheduled_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * 已完成
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * 已取消
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VoucherStatus::Canceled,
        ]);
    }
}
