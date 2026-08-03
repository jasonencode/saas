<?php

namespace App\Rules\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Sku;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 检查商品规格是否存在且商品已上架
 *
 * 用法示例：
 * ```
 * 'sku_id' => [new SkuRule],
 * ```
 */
class SkuRule implements ValidationRule
{
    /**
     * 验证商品规格
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

        if ($sku->product?->status !== ProductStatus::Up) {
            $fail('商品不存在或已下架');
        }
    }
}
