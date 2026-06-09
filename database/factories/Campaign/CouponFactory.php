<?php

namespace Database\Factories\Campaign;

use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\ExpiredType;
use App\Models\Campaign\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->word().'优惠券',
            'code' => Str::upper(Str::random(10)),
            'description' => $this->faker->sentence(),
            'type' => CouponType::Fixed,
            'value' => $this->faker->randomFloat(2, 1, 100),
            'min_amount' => null,
            'max_discount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'expired_type' => ExpiredType::Fixed,
            'days' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'status' => true,
        ];
    }

    /**
     * 固定金额优惠券
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
            'value' => 10.00,
        ]);
    }

    /**
     * 百分比优惠券
     */
    public function percent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Percent,
            'value' => 80,
            'max_discount' => 50.00,
        ]);
    }

    /**
     * 已禁用
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * 已过期
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => now()->subMonth(),
            'end_at' => now()->subDay(),
        ]);
    }

    /**
     * 设置最低消费
     */
    public function withMinAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'min_amount' => $amount,
        ]);
    }

    /**
     * 设置发放上限
     */
    public function withUsageLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
        ]);
    }

    /**
     * 设置每人限领
     */
    public function withUsageLimitPerUser(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit_per_user' => $limit,
        ]);
    }
}
