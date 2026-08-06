<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\DeliveryType;
use App\Models\Mall\Delivery;
use App\Models\Mall\DeliveryRule;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use Illuminate\Support\Collection;

class DeliveryService implements ServiceInterface
{
    /**
     * 获取租户的默认运费模板
     *
     * @param  int  $tenantId  租户 ID
     *
     * @return Delivery|null 运费模板
     */
    public function getDefaultForTenant(int $tenantId): ?Delivery
    {
        return Delivery::where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->where('status', true)
            ->first();
    }

    /**
     * 计算整单运费
     *
     * @param  Delivery  $delivery  运费模板
     * @param  Collection  $items  订单商品列表
     * @param  int|null  $provinceId  省份 ID
     * @param  int|null  $cityId  城市 ID
     * @param  int|null  $districtId  区县 ID
     *
     * @return string 运费金额
     */
    public function calculateOrderFreight(
        Delivery $delivery,
        Collection $items,
        ?int $provinceId = null,
        ?int $cityId = null,
        ?int $districtId = null
    ): string {
        if (!$delivery->status) {
            return '0.00';
        }

        if ($items->isEmpty()) {
            return '0.00';
        }

        $rule = $this->findMatchingRule($delivery, $provinceId, $cityId, $districtId);

        [$totalWeight, $totalCount, $totalVolume, $totalAmount] = $this->calculateOrderTotals($items);

        return $this->calculateFreight($delivery, $rule, $totalWeight, $totalCount, $totalVolume, $totalAmount);
    }

    /**
     * 查找匹配的配送规则
     *
     * 支持降级匹配：district → city → province → 全国通用
     */
    private function findMatchingRule(
        Delivery $delivery,
        ?int $provinceId,
        ?int $cityId,
        ?int $districtId
    ): ?DeliveryRule {
        // 优先匹配区县级规则
        if ($districtId) {
            $rule = $delivery->rules()->orderBy('sort')
                ->where('district_id', $districtId)
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 降级匹配市级规则
        if ($cityId) {
            $rule = $delivery->rules()->orderBy('sort')
                ->where('city_id', $cityId)
                ->whereNull('district_id')
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 降级匹配省级规则
        if ($provinceId) {
            $rule = $delivery->rules()->orderBy('sort')
                ->where('province_id', $provinceId)
                ->whereNull('city_id')
                ->whereNull('district_id')
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 匹配全国通用规则（省市区都为空）
        return $delivery->rules()->orderBy('sort')
            ->whereNull('province_id')
            ->whereNull('city_id')
            ->whereNull('district_id')
            ->first();
    }

    /**
     * 计算订单总量
     *
     * 重量与体积优先取自 SKU（实际承载字段），SKU 缺失时回退到商品。
     * 兼容三种订单项形状：CartItem（product/sku）、OrderItemDto（orderable 是 Sku）、测试 stdClass。
     *
     * @return array{int, int, string, string} [总重量(克), 总件数, 总体积(立方米), 总金额]
     */
    private function calculateOrderTotals(Collection $items): array
    {
        $totalWeight = 0;
        $totalCount = 0;
        $totalVolume = '0.00';
        $totalAmount = '0.00';

        foreach ($items as $item) {
            $qty = max(1, $item->qty ?? 1);
            $totalCount += $qty;

            $sku = $this->resolveSku($item);
            $product = $this->resolveProduct($item);

            // 重量/体积优先取 SKU，SKU 无值再回退商品
            $weightSource = ($sku && $this->hasAttribute($sku, 'weight')) ? $sku : $product;
            $volumeSource = ($sku && $this->hasAttribute($sku, 'volume')) ? $sku : $product;

            if ($weightSource && $this->hasAttribute($weightSource, 'weight')) {
                $weightInGrams = bcmul((string) $weightSource->weight, '1000', 0);
                $totalWeight += (int) bcmul($weightInGrams, (string) $qty, 0);
            }

            if ($volumeSource && $this->hasAttribute($volumeSource, 'volume')) {
                $totalVolume = bcadd($totalVolume, bcmul((string) $volumeSource->volume, (string) $qty, 2), 2);
            }

            if (method_exists($item, 'getAmount')) {
                $totalAmount = bcadd($totalAmount, (string) $item->getAmount(), 2);
            }
        }

        return [$totalWeight, $totalCount, $totalVolume, $totalAmount];
    }

    /**
     * 从订单项解析 SKU 实体
     *
     * @param  mixed  $item  订单项（CartItem / OrderItemDto / stdClass）
     */
    private function resolveSku(mixed $item): ?Sku
    {
        // OrderItemDto: orderable 即为 Sku
        if (isset($item->orderable) && $item->orderable instanceof Sku) {
            return $item->orderable;
        }

        // CartItem / 测试对象：取 sku 关联
        $sku = $item->sku ?? null;

        return $sku instanceof Sku ? $sku : null;
    }

    /**
     * 从订单项解析商品实体
     *
     * @param  mixed  $item  订单项（CartItem / OrderItemDto / stdClass）
     */
    private function resolveProduct(mixed $item): ?Product
    {
        // 直接 product 关联
        if (isset($item->product) && $item->product instanceof Product) {
            return $item->product;
        }

        // 通过 SKU 反查商品
        $sku = $this->resolveSku($item);

        return $sku?->product instanceof Product ? $sku->product : null;
    }

    /**
     * 安全检测模型/对象是否拥有某属性且非 null
     */
    private function hasAttribute(mixed $entity, string $key): bool
    {
        if ($entity === null) {
            return false;
        }

        // Eloquent 模型
        if (method_exists($entity, 'getAttribute')) {
            return $entity->getAttribute($key) !== null;
        }

        // 普通 stdClass / 动态对象
        return isset($entity->{$key});
    }

    /**
     * 计算单模板运费
     *
     * @param  Delivery  $delivery  运费模板
     * @param  DeliveryRule|null  $rule  命中的特殊规则（无则用模板默认值）
     * @param  int  $totalWeight  订单总重量（克）
     * @param  int  $totalCount  订单总件数
     * @param  string  $totalVolume  订单总体积（立方米）
     * @param  string  $totalAmount  订单总金额
     *
     * @return string 运费金额
     */
    private function calculateFreight(
        Delivery $delivery,
        ?DeliveryRule $rule,
        int $totalWeight,
        int $totalCount,
        string $totalVolume,
        string $totalAmount
    ): string {
        $firstFee = (string) ($rule?->first_fee ?? $delivery->first_fee);
        $firstValue = (string) ($rule?->first ?? $delivery->first);
        $additionalValue = (string) ($rule?->additional ?? $delivery->additional);
        $additionalFee = (string) ($rule?->additional_fee ?? $delivery->additional_fee);
        // null 表示沿用模板：规则未配置则取模板值；模板为 null 则视为 0（不包邮）
        $freeShippingThreshold = $rule?->free_shipping_threshold ?? $delivery->free_shipping_threshold ?? '0';

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
            DeliveryType::Count => $this->calculateAdditionalFreight(
                $firstFee,
                $firstValue,
                $additionalValue,
                $additionalFee,
                (string) $totalCount
            ),
            DeliveryType::Size => $this->calculateAdditionalFreight(
                $firstFee,
                $firstValue,
                $additionalValue,
                $additionalFee,
                $totalVolume
            ),
        };
    }

    /**
     * 计算附加运费
     *
     * @param  string  $firstFee  首件费用
     * @param  string  $firstValue  首件数量
     * @param  string  $additionalValue  续件数量
     * @param  string  $additionalFee  续件费用
     * @param  string  $totalTotal  总数量
     *
     * @return string 运费金额
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
     *
     * @param  string  $dividend  被除数
     * @param  string  $divisor  除数
     *
     * @return int 向上取整的结果
     */
    private function ceilDivision(string $dividend, string $divisor): int
    {
        if (bccomp($divisor, '0', 10) === 0) {
            return 0;
        }

        $quotient = bcdiv($dividend, $divisor, 10);
        $wholePart = strstr($quotient, '.', true);
        $wholePart = $wholePart === false ? $quotient : $wholePart;

        if (bccomp($quotient, $wholePart, 10) === 1) {
            return (int) $wholePart + 1;
        }

        return (int) $wholePart;
    }
}
