<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\InvoiceTitleType;
use App\Models\Finance\InvoiceTitle;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceTitle>
 */
class InvoiceTitleFactory extends Factory
{
    protected $model = InvoiceTitle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'type' => InvoiceTitleType::Personal,
            'title' => $this->faker->name(),
            'tax_no' => null,
            'company_address' => null,
            'company_phone' => null,
            'bank_name' => null,
            'bank_account' => null,
            'email' => $this->faker->safeEmail(),
            'is_default' => false,
        ];
    }

    /**
     * 企业抬头
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InvoiceTitleType::Enterprise,
            'title' => $this->faker->company(),
            'tax_no' => $this->faker->numerify('91110108MA01XXXXXX'),
            'company_address' => $this->faker->address(),
            'company_phone' => $this->faker->phoneNumber(),
            'bank_name' => $this->faker->word().'银行',
            'bank_account' => $this->faker->bankAccountNumber(),
        ]);
    }

    /**
     * 设为默认
     */
    public function asDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
