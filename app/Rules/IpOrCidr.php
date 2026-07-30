<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IpOrCidr implements ValidationRule
{
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
