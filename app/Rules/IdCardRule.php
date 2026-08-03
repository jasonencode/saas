<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 验证中国大陆居民身份证号码
 *
 * 校验规则：18 位长度、合法省份代码、合法出生日期、正确校验码。
 *
 * 用法示例：
 * ```
 * 'id_card' => [new IdCardRule],
 * ```
 */
class IdCardRule implements ValidationRule
{
    private const int ID_LENGTH = 18;

    private const array WEIGHTS = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

    private const array VERIFY_CODES = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

    private const array PROVINCE_CODES = [
        11, 12, 13, 14, 15,
        21, 22, 23,
        31, 32, 33, 34, 35, 36, 37,
        41, 42, 43, 44,
        45, 46,
        50, 51, 52, 53, 54,
        61, 62, 63, 64, 65,
        71,
        81, 82,
    ];

    /**
     * 验证身份证号码
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  身份证号码
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('身份证号码必须是字符串');

            return;
        }

        $value = trim($value);

        if (strlen($value) !== self::ID_LENGTH) {
            $fail('请输入18位身份证号码');

            return;
        }

        if (!$this->isIdCard($value)) {
            $fail($this->getErrorMessage($value));
        }
    }

    /**
     * 验证身份证号码是否有效
     *
     * @param  string  $id  身份证号码
     *
     * @return bool 是否有效
     */
    private function isIdCard(string $id): bool
    {
        $id = strtoupper($id);

        return $this->matchesBasicFormat($id)
            && $this->hasValidProvinceCode($id)
            && $this->hasValidBirthDate($id)
            && $this->hasValidChecksum($id);
    }

    /**
     * 验证身份证号码基本格式
     *
     * @param  string  $id  身份证号码
     *
     * @return bool 格式是否正确
     */
    private function matchesBasicFormat(string $id): bool
    {
        return (bool) preg_match('/^\d{17}[0-9X]$/', $id);
    }

    /**
     * 验证省份代码是否有效
     *
     * @param  string  $id  身份证号码
     *
     * @return bool 省份代码是否有效
     */
    private function hasValidProvinceCode(string $id): bool
    {
        $provinceCode = (int) substr($id, 0, 2);

        return in_array($provinceCode, self::PROVINCE_CODES, true);
    }

    /**
     * 验证出生日期是否有效
     *
     * @param  string  $id  身份证号码
     *
     * @return bool 出生日期是否有效
     */
    private function hasValidBirthDate(string $id): bool
    {
        if (!preg_match('/^.{6}(\d{4})(\d{2})(\d{2})/', $id, $matches)) {
            return false;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year < 1900 || $year > (int) date('Y')) {
            return false;
        }

        return checkdate($month, $day, $year);
    }

    /**
     * 验证校验码是否有效
     *
     * @param  string  $id  身份证号码
     *
     * @return bool 校验码是否有效
     */
    private function hasValidChecksum(string $id): bool
    {
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += (int) $id[$i] * self::WEIGHTS[$i];
        }

        $checkCode = self::VERIFY_CODES[$sum % 11];

        return $checkCode === $id[17];
    }

    /**
     * 获取错误消息
     *
     * @param  string  $id  身份证号码
     *
     * @return string 错误消息
     */
    private function getErrorMessage(string $id): string
    {
        $id = strtoupper($id);

        if (!$this->matchesBasicFormat($id)) {
            return '身份证号码格式不正确';
        }

        if (!$this->hasValidProvinceCode($id)) {
            return '身份证号码的省份代码不正确';
        }

        if (!$this->hasValidBirthDate($id)) {
            return '身份证号码的出生日期不正确';
        }

        return '身份证号码的校验码不正确';
    }
}
