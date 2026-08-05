<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\BaseFormRequest;

class RefundRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
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
            'amount.required' => '退款金额必须填写',
            'amount.numeric' => '退款金额格式不正确',
            'amount.min' => '退款金额最小为:min',
            'reason.required' => '退款原因必须填写',
            'reason.string' => '退款原因格式不正确',
            'reason.max' => '退款原因最多:max位字符',
        ];
    }
}
