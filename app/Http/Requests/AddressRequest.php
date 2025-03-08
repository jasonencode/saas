<?php

namespace App\Http\Requests;

use App\Enums\RegionLevel;
use App\Rules\RegionRule;

class AddressRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:2|max:16',
            'mobile' => ['required', 'numeric', 'regex:/^1[3-9]\d{9}$/'],
            'province_id' => ['required', 'numeric', new RegionRule(RegionLevel::Province)],
            'city_id' => ['required', 'numeric', new RegionRule(RegionLevel::City)],
            'district_id' => ['required', 'numeric', new RegionRule(RegionLevel::District)],
            'address' => 'required|min:2|max:255',
            'is_default' => 'sometimes|required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '收件人必须填写',
            'name.min' => '收件人姓名至少:min位字符',
            'name.max' => '收件人姓名最多:max位字符',
            'mobile.required' => '手机号码必须填写',
            'mobile.numeric' => '手机号码必须是数字',
            'mobile.regex' => '手机号码格式不正确',
            'province_id.required' => '所在省份必须填写',
            'province_id.numeric' => '所在省份格式不正确',
            'city_id.required' => '所在城市必须填写',
            'city_id.numeric' => '所在城市格式不正确',
            'district_id.required' => '所在区县必须填写',
            'district_id.numeric' => '所在区县格式不正确',
            'address.required' => '详细地址必须填写',
            'address.min' => '详细地址至少:min位字符',
            'address.max' => '详细地址最多:max位字符',
        ];
    }
}