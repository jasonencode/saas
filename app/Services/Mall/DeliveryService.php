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
     * 计算整单运费
     */
    public function calculateOrderFreight(
        Delivery $delivery,
        Collection $items,
        ?int $provinceId = null,
        ?int $cityId = null,
        ?int $districtId = null
    ): string {
        if (! $delivery->status) {
            return '0.00';
        }

        if ($items->isEmpty()) {
            return '0.00';
        }

        $rule = $this->findMatchingRule($delivery, $provinceId, $cityId, $districtId);

        [$totalWeight, $totalCount, $totalAmount] = $this->calculateOrderTotals($items);

        return $this->calculateFreight($delivery, $rule, $totalWeight, $totalCount, $totalAmount);
    }

    /**
     * 查找匹配的配送规则
     */
    private function findMatchingRule(
        Delivery $delivery,
        ?int $provinceId,
        ?int $cityId,
        ?int $districtId
    ): ?DeliveryRule {
        $query = $delivery->rules()->orderBy('sort');

        return $query
            ->when($districtId, fn ($q) => $q->where('district_id', $districtId))
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($provinceId, fn ($q) => $q->where('province_id', $provinceId))
            ->first();
    }

    /**
     * 计算订单总量
     *
     * @return array{int, int, string}
     */
    private function calculateOrderTotals(Collection $items): array
    {
        $totalWeight = 0;
        $totalCount = 0;
        $totalAmount = '0.00';

        foreach ($items as $item) {
            $qty = max(1, $item->qty ?? 1);
            $totalCount += $qty;

            $product = $item->product ?? ($item->sku?->product);
            if ($product && property_exists($product, 'weight')) {
                $weightInGrams = bcmul((string) $product->weight, '1000', 0);
                $totalWeight += (int) bcmul($weightInGrams, (string) $qty, 0);
            }

            if (method_exists($item, 'getAmount')) {
                $totalAmount = bcadd($totalAmount, (string) $item->getAmount(), 2);
            }
        }

        return [$totalWeight, $totalCount, $totalAmount];
    }

    /**
     * 计算运费
     */
    private function calculateFreight(
        Delivery $delivery,
        ?DeliveryRule $rule,
        int $totalWeight,
        int $totalCount,
        string $totalAmount
    ): string {
        $firstFee = (string) ($rule?->first_fee ?? $delivery->first_fee);
        $firstValue = (string) ($rule?->first ?? $delivery->first);
        $additionalValue = (string) ($rule?->additional ?? $delivery->additional);
        $additionalFee = (string) ($rule?->additional_fee ?? $delivery->additional_fee);
        $freeShippingThreshold = (string) ($rule?->free_shipping_threshold ?? $delivery->free_shipping_threshold);

        if (bccomp($freeShippingThreshold, '0', 2) > 0 && bccomp($totalAmount, $freeShippingThreshold, 2) >= 0) {
            return '0.00';
        }

        return match ($delivery->type) {
            DeliveryType::Weight => $this->calculateAdditionalFreight(
                $firstFee,
                bcmul($firstValue, '1000', 0),
                bcmul($additionalValue, '1000', 0),
                $additionalFee,
                (string) $totalWeight
            ),
            DeliveryType::Count, DeliveryType::Size => $this->calculateAdditionalFreight(
                $firstFee,
                $firstValue,
                $additionalValue,
                $additionalFee,
                (string) $totalCount
            ),
        };
    }

    /**
     * 计算附加运费
     */
    private function calculateAdditionalFreight(
        string $firstFee,
        string $firstValue,
        string $additionalValue,
        string $additionalFee,
        string $totalTotal
    ): string {
        if (bccomp($totalTotal, $firstValue, 2) <= 0) {
            return $firstFee;
        }

        $additionalTotal = bcsub($totalTotal, $firstValue, 2);
        $additionalUnits = $this->ceilDivision($additionalTotal, $additionalValue);

        return bcadd($firstFee, bcmul((string) $additionalUnits, $additionalFee, 2), 2);
    }

    /**
     * 十进制字符串除法向上取整
     */
    private function ceilDivision(string $dividend, string $divisor): int
    {
        $quotient = bcdiv($dividend, $divisor, 10);
        $wholePart = strstr($quotient, '.', true);
        $wholePart = $wholePart === false ? $quotient : $wholePart;

        if (bccomp($quotient, $wholePart, 10) === 1) {
            return (int) $wholePart + 1;
        }

        return (int) $wholePart;
    }
}
