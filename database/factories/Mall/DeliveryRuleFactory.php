<?php

namespace Database\Factories\Mall;

use App\Models\Mall\DeliveryRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryRule>
 */
class DeliveryRuleFactory extends Factory
{
    protected $model = DeliveryRule::class;

    public function definition(): array
    {
        return [
            'delivery_id' => null,
            'province_id' => null,
            'city_id' => null,
            'district_id' => null,
            'region_code' => null,
            'region_name' => $this->faker->city(),
            'first' => 1,
            'first_fee' => 15.00,
            'additional' => 1,
            'additional_fee' => 10.00,
            'free_shipping_threshold' => 0,
            'sort' => 0,
        ];
    }

    /**
     * 设置省份
     */
    public function forProvince(int $provinceId): static
    {
        return $this->state(fn (array $attributes) => [
            'province_id' => $provinceId,
        ]);
    }

    /**
     * 设置城市
     */
    public function forCity(int $provinceId, int $cityId): static
    {
        return $this->state(fn (array $attributes) => [
            'province_id' => $provinceId,
            'city_id' => $cityId,
        ]);
    }

    /**
     * 设置区县
     */
    public function forDistrict(int $provinceId, int $cityId, int $districtId): static
    {
        return $this->state(fn (array $attributes) => [
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
        ]);
    }

    /**
     * 设置包邮门槛
     */
    public function withFreeShipping(float $threshold): static
    {
        return $this->state(fn (array $attributes) => [
            'free_shipping_threshold' => $threshold,
        ]);
    }
}
