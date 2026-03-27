<?php

namespace App\Http\Requests;

class TenantTokenRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'app_key' => 'required|string',
            'timestamp' => 'required|integer',
            'nonce' => 'required|string',
            'signature' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'app_key.required' => '应用 Key 必须填写',
            'timestamp.required' => '时间戳必须填写',
            'timestamp.integer' => '时间戳格式不正确',
            'nonce.required' => '随机字符串必须填写',
            'signature.required' => '签名必须填写',
        ];
    }
}
