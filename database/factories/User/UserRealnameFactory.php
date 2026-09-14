<?php

namespace Database\Factories\User;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRealname>
 */
class UserRealnameFactory extends Factory
{
    protected $model = UserRealname::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => RealnameType::Personal,
            'name' => $this->faker->name(),
            'id_card_number' => $this->faker->idNumber(),
            'id_card_front' => null,
            'id_card_back' => null,
            'business_license' => null,
            'status' => RealnameStatus::Pending,
            'verified_at' => null,
            'reject_reason' => null,
        ];
    }

    /**
     * 企业认证
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RealnameType::Enterprise,
            'id_card_number' => null,
            'business_license' => 'business.jpg',
        ]);
    }

    /**
     * 已通过
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RealnameStatus::Approved,
            'verified_at' => now(),
        ]);
    }

    /**
     * 已拒绝
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RealnameStatus::Rejected,
            'reject_reason' => $this->faker->sentence(),
        ]);
    }
}
