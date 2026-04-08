<?php

namespace App\Rules;

use App\Models\User\Address;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class OrderAddressRule implements ValidationRule
{
    protected array $data;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $address = Address::find($value);

        if (empty($address)) {
            $fail('地址不存在');

            return;
        }

        if ($address->user->isNot(Auth::user())) {
            $fail('地址不存在');
        }
    }
}
