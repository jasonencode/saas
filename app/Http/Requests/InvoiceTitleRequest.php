<?php

namespace App\Http\Requests;

use App\Enums\InvoiceTitleType;
use Illuminate\Validation\Rules\Enum;

class InvoiceTitleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(InvoiceTitleType::class)],
            'name' => 'required|min:2|max:100',
            'tax_no' => 'sometimes|nullable|string|regex:/^[0-9A-Z]{15,20}$/',
            'is_default' => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => '发票类型必须选择',
            'name.required' => '发票抬头必须填写',
            'name.min' => '发票抬头至少:min位字符',
            'name.max' => '发票抬头最多:max位字符',
            'tax_id.regex' => '税号格式不正确（15-20位数字或大写字母）',
        ];
    }
}
