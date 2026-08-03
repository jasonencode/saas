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
                $fail("The IP-Cidr address '$value' is not valid.");
            }
        } elseif (!filter_var($value, FILTER_VALIDATE_IP)) {
            $fail("The IP address '$value' is not valid.");
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

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (!ctype_digit($prefix)) {
            return false;
        }

        $prefix = (int) $prefix;
        $maxPrefix = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32;

        return $prefix >= 1 && $prefix <= $maxPrefix;
    }
}
