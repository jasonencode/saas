<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Models\Finance\InvoiceApplication;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceApplication>
 */
class InvoiceApplicationFactory extends Factory
{
    protected $model = InvoiceApplication::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'invoice_title_id' => null,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'reason' => $this->faker->optional()->sentence(),
            'status' => InvoiceApplicationStatus::Pending,
            'title_snapshot' => null,
        ];
    }

    /**
     * 已开票
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceApplicationStatus::Completed,
        ]);
    }

    /**
     * 已拒绝
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceApplicationStatus::Rejected,
        ]);
    }
}
