<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IpOrCidr implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
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
        [$ip, $prefix] = explode('/', $cidr);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $prefix = (int) $prefix;

        return !($prefix < 0 || $prefix > 32);
    }
}
