<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\DeliveryType;
use App\Models\Mall\Delivery;
use App\Models\Mall\DeliveryRule;
use Illuminate\Support\Collection;

class DeliveryService implements ServiceInterface
{
    /**
     * 获取租户的默认运费模板
     */
    public function getDefaultForTenant(int $tenantId): ?Delivery
    {
        return Delivery::query()
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->where('status', true)
            ->first();
    }

    /**
     * 计算整单运费（按商品集合）
     *
     * @param  Delivery  $delivery  运费模板
     * @param  Collection  $items  商品项集合
     * @param  int|null  $provinceId  省份ID
     * @param  int|null  $cityId  城市ID
     * @param  int|null  $districtId  区县ID
     * @return float 运费
     */
    public function calculateOrderFreight(Delivery $delivery, Collection $items, ?int $provinceId = null, ?int $cityId = null, ?int $districtId = null): float
    {
        if (!$delivery->status) {
            return 0.00;
        }

        // 如果没有商品，直接返回0
        if ($items->isEmpty()) {
            return 0.00;
        }

        $rule = $this->findMatchingRule($delivery, $provinceId, $cityId, $districtId);

        [$totalWeight, $totalCount, $totalAmount] = $this->calculateOrderTotals($items);

        return $this->calculateFreight($delivery, $rule, $totalWeight, $totalCount, $totalAmount);
    }

    /**
     * 查找匹配的配送规则（按省市区优先级匹配）
     */
    private function findMatchingRule(Delivery $delivery, ?int $provinceId, ?int $cityId, ?int $districtId): ?DeliveryRule
    {
        $query = $delivery->rules()->orderBy('sort');

        return $query
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId))
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($provinceId, fn ($q) => $q->where('province_id', $provinceId))
            ->first();
    }

    /**
     * 计算订单总额（重量、数量、金额）
     *
     * @return array [totalWeight, totalCount, totalAmount]
     */
    private function calculateOrderTotals(Collection $items): array
    {
        $totalWeight = 0;
        $totalCount = 0;
        $totalAmount = 0.00;

        foreach ($items as $item) {
            $qty = max(1, $item->qty ?? 1);
            $totalCount += $qty;

            // 计算重量（克）
            $product = $item->product ?? ($item->sku?->product);
            if ($product && property_exists($product, 'weight')) {
                $totalWeight += (int) ($product->weight * 1000 * $qty);
            }

            // 计算金额
            if (method_exists($item, 'getAmount')) {
                $totalAmount += (float) $item->getAmount();
            }
        }

        return [$totalWeight, $totalCount, $totalAmount];
    }

    /**
     * 计算运费（通用逻辑）
     *
     * @param  Delivery  $delivery  运费模板
     * @param  DeliveryRule|null  $rule  配送规则
     * @param  int  $totalWeight  总重量（克）
     * @param  int  $totalCount  总数量
     * @param  float  $totalAmount  总金额
     * @return float 运费
     */
    private function calculateFreight(Delivery $delivery, ?DeliveryRule $rule, int $totalWeight, int $totalCount, float $totalAmount): float
    {
        $firstFee = $rule?->first_fee ?? $delivery->first_fee;
        $firstValue = $rule?->first ?? $delivery->first;
        $additionalValue = $rule?->additional ?? $delivery->additional;
        $additionalFee = $rule?->additional_fee ?? $delivery->additional_fee;
        $freeShippingThreshold = $rule?->free_shipping_threshold ?? $delivery->free_shipping_threshold;

        // 检查是否包邮
        if ($freeShippingThreshold > 0 && $totalAmount >= $freeShippingThreshold) {
            return 0.00;
        }

        // 根据类型计算运费
        match ($delivery->type) {
            DeliveryType::Weight => $freight = $this->calculateAdditionalFreight(
                $firstFee,
                $firstValue * 1000, // 转换为克
                $additionalValue * 1000, // 转换为克
                $additionalFee,
                $totalWeight
            ),
            DeliveryType::Count, DeliveryType::Size => $freight = $this->calculateAdditionalFreight(
                $firstFee,
                $firstValue,
                $additionalValue,
                $additionalFee,
                $totalCount
            ),
        };

        return $freight;
    }

    /**
     * 计算额外运费
     *
     * @param  float  $firstFee  首件/首重费用
     * @param  float  $firstValue  首件/首重值
     * @param  float  $additionalValue  续件/续重值
     * @param  float  $additionalFee  续件/续重费用
     * @param  float  $totalTotal  总重量/总数量
     * @return float 额外运费
     */
    private function calculateAdditionalFreight(float $firstFee, float $firstValue, float $additionalValue, float $additionalFee, float $totalTotal): float
    {
        $freight = $firstFee;

        if ($totalTotal > $firstValue) {
            $additionalTotal = $totalTotal - $firstValue;
            $additionalUnits = ceil($additionalTotal / $additionalValue);
            $freight += $additionalUnits * $additionalFee;
        }

        return $freight;
    }
}
