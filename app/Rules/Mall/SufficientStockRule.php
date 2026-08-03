<?php

namespace App\Rules\Mall;

use App\Models\Mall\Sku;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

/**
 * 检查 SKU 库存是否充足
 *
 * 支持两种使用方式：
 * 1. 自动解析：利用 DataAwareRule 从表单数据中读取对应 qty 字段
 * 2. 手动指定：通过构造函数传入数量
 *
 * 用法示例（自动解析）：
 * ```
 * 'sku_id' => [new SkuRule, new SufficientStockRule],
 * 'qty' => 'required|integer|min:1',
 * ```
 *
 * 用法示例（手动指定，适用数组格式）：
 * ```
 * 'skus.*.sku_id' => [new SkuRule, new SufficientStockRule(quantity: 1)],
 * ```
 */
class SufficientStockRule implements DataAwareRule, ValidationRule
{
    /**
     * 表单数据（由 DataAwareRule 注入）
     */
    public array $data = [];

    /**
     * @param  int|null  $quantity  手动指定购买数量（null 则从表单数据自动解析）
     * @param  string|null  $qtyField  qty 字段名，默认 'qty'
     */
    public function __construct(
        protected ?int $quantity = null,
        protected ?string $qtyField = null,
    ) {
        //
    }

    /**
     * 验证 SKU 库存是否充足
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  SKU ID
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sku = Sku::find($value);

        if (!$sku) {
            $fail('您选择的规格不存在');

            return;
        }

        $qty = $this->quantity ?? $this->resolveQtyFromData($attribute);

        if ($qty === null || $qty < 1) {
            $fail('购买数量不正确');

            return;
        }

        if ($sku->stock < $qty) {
            $fail("商品库存不足，当前库存：$sku->stock");
        }
    }

    /**
     * 从表单数据解析购买数量
     *
     * @param  string  $attribute  验证字段名
     *
     * @return int|null 购买数量
     */
    protected function resolveQtyFromData(string $attribute): ?int
    {
        $field = $this->qtyField ?? 'qty';

        // 数组格式：skus.0.sku_id → skus.0.qty
        $parts = explode('.', $attribute);
        if (count($parts) >= 2) {
            $parts[count($parts) - 1] = $field;

            $qty = Arr::get($this->data, implode('.', $parts));

            if ($qty !== null) {
                return (int) $qty;
            }
        }

        // 平铺格式：sku_id → qty
        $qty = Arr::get($this->data, $field);

        return $qty !== null ? (int) $qty : null;
    }

    /**
     * 设置表单数据
     *
     * @param  array  $data  表单数据
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
