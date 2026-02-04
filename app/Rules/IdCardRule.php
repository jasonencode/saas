<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

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

    private function isIdCard(string $id): bool
    {
        $id = strtoupper($id);

        return $this->matchesBasicFormat($id)
            && $this->hasValidProvinceCode($id)
            && $this->hasValidBirthDate($id)
            && $this->hasValidChecksum($id);
    }

    private function matchesBasicFormat(string $id): bool
    {
        return (bool) preg_match('/^\d{17}[0-9X]$/', $id);
    }

    private function hasValidProvinceCode(string $id): bool
    {
        $provinceCode = (int) substr($id, 0, 2);

        return in_array($provinceCode, self::PROVINCE_CODES, true);
    }

    private function hasValidBirthDate(string $id): bool
    {
        if (!preg_match('/^.{6}(\d{4})(\d{2})(\d{2})/', $id, $matches)) {
            return false;
        }

        [, $year, $month, $day] = $matches;

        if ($year < 1900 || $year > date('Y')) {
            return false;
        }

        return checkdate((int) $month, (int) $day, (int) $year);
    }

    private function hasValidChecksum(string $id): bool
    {
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += (int) $id[$i] * self::WEIGHTS[$i];
        }

        $checkCode = self::VERIFY_CODES[$sum % 11];

        return $checkCode === $id[17];
    }

    private function getErrorMessage(string $id): string
    {
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
