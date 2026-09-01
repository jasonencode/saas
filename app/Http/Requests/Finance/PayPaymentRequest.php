<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\BaseFormRequest;

class PayPaymentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'payment_password' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_password.string' => '支付密码格式不正确',
            'payment_password.max' => '支付密码最多:max位字符',
        ];
    }
}
