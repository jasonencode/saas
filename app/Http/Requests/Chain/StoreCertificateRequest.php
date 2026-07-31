<?php

namespace App\Http\Requests\Chain;

use App\Enums\BlockChain\CertificateSignType;
use App\Enums\BlockChain\CertificateType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'common_name' => 'required|string|max:255',
            'type' => ['required', Rule::enum(CertificateType::class)],
            'sign_type' => ['required', Rule::enum(CertificateSignType::class)],
            'country_name' => 'required|string|max:2',
            'state_or_province_name' => 'required|string|max:255',
            'locality_name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'organizational_unit_name' => 'nullable|string|max:255',
            'email_address' => 'required|email|max:255',
            'password' => 'required|string|min:6|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'common_name.required' => '证书名称必须填写',
            'common_name.string' => '证书名称格式不正确',
            'common_name.max' => '证书名称最多:max位字符',
            'type.required' => '证书类型必须填写',
            'sign_type.required' => '签名类型必须填写',
            'country_name.required' => '国家代码必须填写',
            'country_name.max' => '国家代码最多:max位字符',
            'state_or_province_name.required' => '省份必须填写',
            'locality_name.required' => '城市必须填写',
            'organization_name.required' => '组织名称必须填写',
            'email_address.required' => '邮箱必须填写',
            'email_address.email' => '邮箱格式不正确',
            'password.required' => '证书密码必须填写',
            'password.min' => '证书密码至少:min位字符',
            'password.max' => '证书密码最多:max位字符',
        ];
    }
}
