<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class MobileRule implements ValidationRule
{
    private const int MOBILE_LENGTH = 11;

    /**
     * 运营商号段
     */
    private const array CARRIER_SEGMENTS = [
        '13' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        '14' => ['5', '6', '7', '8', '9'],
        '15' => ['0', '1', '2', '3', '5', '6', '7', '8', '9'],
        '16' => ['1', '2', '5', '6', '7'],
        '17' => ['0', '1', '2', '3', '4', '5', '6', '7', '8'],
        '18' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        '19' => ['0', '1', '2', '3', '5', '6', '7', '8', '9'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $this->validateMobile($value);
        } catch (InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }

    private function validateMobile(mixed $value): void
    {
        if (!is_string($value) && !is_numeric($value)) {
            throw new InvalidArgumentException('手机号码格式不正确');
        }

        $value = $this->normalizeInput($value);

        if (!$this->isValidLength($value)) {
            throw new InvalidArgumentException('手机号码必须是11位数字');
        }

        if (!$this->isValidCarrierSegment($value)) {
            throw new InvalidArgumentException('手机号码的号段不正确');
        }
    }

    private function normalizeInput(mixed $value): string
    {
        return trim((string) $value);
    }

    private function isValidLength(string $mobile): bool
    {
        return strlen($mobile) === self::MOBILE_LENGTH;
    }

    private function isValidCarrierSegment(string $mobile): bool
    {
        $prefix = substr($mobile, 0, 2);
        $thirdDigit = $mobile[2];

        return isset(self::CARRIER_SEGMENTS[$prefix])
            && in_array($thirdDigit, self::CARRIER_SEGMENTS[$prefix], true);
    }
}
