<?php

namespace App\Rules;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Sku;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SkuRule implements ValidationRule
{
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