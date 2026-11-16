<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 验证 IP 地址或 CIDR 网段
 *
 * 用法示例：
 * ```
 * 'ip' => [new IpOrCidr],
 * ```
 */
class IpOrCidr implements ValidationRule
{
    /**
     * 验证 IP 地址或 CIDR
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  IP 地址或 CIDR
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The IP address is not valid.');

            return;
        }

        if (str_contains($value, '/')) {
            if (!$this->validateCidr($value)) {
                $fail("仅支持 IPv4 网段，'{$value}' 不是有效的 CIDR 格式。");
            }
        } elseif (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $fail("仅支持 IPv4 地址，'{$value}' 不是有效的 IP。");
        }
    }

    /**
     * 验证 CIDR 格式
     *
     * @param  string  $cidr  CIDR 字符串
     *
     * @return bool 是否有效
     */
    private function validateCidr(string $cidr): bool
    {
        $parts = explode('/', $cidr);

        if (count($parts) !== 2) {
            return false;
        }

        [$ip, $prefix] = $parts;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        if (!ctype_digit($prefix)) {
            return false;
        }

        $prefix = (int) $prefix;

        return $prefix >= 1 && $prefix <= 32;
    }
}
