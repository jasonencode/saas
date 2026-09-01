<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * 验证支付密码
 *
 * 校验规则：6 位纯数字、不能为重复数字（如 111111）、不能为连续数字（如 123456、987654，含循环移位如 789012）。
 *
 * 用法示例：
 * ```
 * 'password' => [new PaymentPasswordRule],
 * ```
 */
class PaymentPasswordRule implements ValidationRule
{
    private const int PASSWORD_LENGTH = 6;

    /**
     * 验证支付密码
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  支付密码
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $this->validatePassword($value);
        } catch (InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }

    /**
     * 验证支付密码
     *
     * @param  mixed  $value  支付密码
     *
     * @throws InvalidArgumentException 支付密码格式不正确
     */
    private function validatePassword(mixed $value): void
    {
        if (!is_string($value) && !is_numeric($value)) {
            throw new InvalidArgumentException('支付密码格式不正确');
        }

        $value = $this->normalizeInput($value);

        if (!ctype_digit($value)) {
            throw new InvalidArgumentException('支付密码必须为纯数字');
        }

        if (strlen($value) !== self::PASSWORD_LENGTH) {
            throw new InvalidArgumentException('支付密码必须是 6 位数字');
        }

        if ($this->isRepeated($value)) {
            throw new InvalidArgumentException('支付密码不能为重复数字');
        }

        if ($this->isConsecutive($value)) {
            throw new InvalidArgumentException('支付密码不能为连续数字');
        }
    }

    /**
     * 标准化输入
     *
     * @param  mixed  $value  原始输入
     *
     * @return string 标准化后的字符串
     */
    private function normalizeInput(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * 是否为重复数字（如 111111）
     *
     * @param  string  $password  支付密码
     *
     * @return bool 是否为重复数字
     */
    private function isRepeated(string $password): bool
    {
        return count(array_unique(str_split($password))) === 1;
    }

    /**
     * 是否为连续数字（升序或降序，含循环移位如 789012、321098）
     *
     * @param  string  $password  支付密码
     *
     * @return bool 是否为连续数字
     */
    private function isConsecutive(string $password): bool
    {
        $digits = array_map('intval', str_split($password));

        return $this->isAscendingSequence($digits) || $this->isDescendingSequence($digits);
    }

    /**
     * 是否为升序连续（含循环移位，如 123456、789012）
     *
     * @param  array<int, int>  $digits  数字数组
     *
     * @return bool 是否为升序连续
     */
    private function isAscendingSequence(array $digits): bool
    {
        for ($i = 1, $iMax = count($digits); $i < $iMax; $i++) {
            if (($digits[$i] - $digits[$i - 1] + 10) % 10 !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * 是否为降序连续（含循环移位，如 987654、321098）
     *
     * @param  array<int, int>  $digits  数字数组
     *
     * @return bool 是否为降序连续
     */
    private function isDescendingSequence(array $digits): bool
    {
        for ($i = 1, $iMax = count($digits); $i < $iMax; $i++) {
            if (($digits[$i] - $digits[$i - 1] + 10) % 10 !== 9) {
                return false;
            }
        }

        return true;
    }
}
