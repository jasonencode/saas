<?php

namespace App\Rules\Mall;

use App\Services\Mall\OrderableResolver;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class OrderableRule implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $wildcard = preg_replace('/\.\d+\./', '.*.', $attribute, 1);
        $typePath = str_replace('orderable_id', 'orderable_type', $wildcard);

        $type = Arr::get($this->data, $typePath);

        if (!$type) {
            $fail('商品类型参数缺失');

            return;
        }

        $orderable = OrderableResolver::resolve($type, (int) $value);

        if (!$orderable) {
            $fail('所选商品不存在');
        }
    }
}
