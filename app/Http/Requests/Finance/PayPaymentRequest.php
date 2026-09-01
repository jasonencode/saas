<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\BaseFormRequest;
use App\Rules\PaymentPasswordRule;

class PayPaymentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|PaymentPasswordRule>>
     */
    public function rules(): array
    {
        return [
            'payment_password' => ['nullable', new PaymentPasswordRule],
        ];
    }
}
