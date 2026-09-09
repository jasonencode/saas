<?php

namespace App\Http\Requests\User;

use App\Enums\User\RealnameType;
use App\Http\Requests\BaseFormRequest;
use App\Rules\FileExistsRule;
use App\Rules\IdCardRule;
use Illuminate\Validation\Rule;

class StoreUserRealnameRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array<int, string|Rule|IdCardRule|FileExistsRule>>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'type' => ['required', 'string', Rule::enum(RealnameType::class)],
        ];

        // 个人认证
        if ($type === RealnameType::Personal->value) {
            $rules['name'] = 'required|string|max:64';
            $rules['id_card_number'] = ['required', 'string', new IdCardRule];
            $rules['id_card_front'] = ['required', 'string', new FileExistsRule];
            $rules['id_card_back'] = ['required', 'string', new FileExistsRule];

            return $rules;
        }

        // 企业认证
        $rules['name'] = 'required|string|max:128';
        $rules['business_license'] = ['required', 'string', new FileExistsRule];
        $rules['contact_person'] = 'required|string|max:32';
        $rules['contact_phone'] = 'required|string|max:20';

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => '认证类型必须选择',
            'type.in' => '认证类型不支持',
            'name.required' => '姓名/企业名称必须填写',
            'name.string' => '姓名/企业名称格式不正确',
            'name.max' => '姓名/企业名称最多:max个字符',
            'id_card_number.required' => '身份证号码必须填写',
            'id_card_number.string' => '身份证号码格式不正确',
            'id_card_front.required' => '请先上传身份证正面照',
            'id_card_back.required' => '请先上传身份证背面照',
            'business_license.required' => '请先上传营业执照',
            'contact_person.required' => '联系人必须填写',
            'contact_person.max' => '联系人最多:max个字符',
            'contact_phone.required' => '联系电话必须填写',
            'contact_phone.max' => '联系电话最多:max个字符',
        ];
    }
}
