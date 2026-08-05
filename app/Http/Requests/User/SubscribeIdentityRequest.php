<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class SubscribeIdentityRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'qty' => 'sometimes|integer|min:1|max:99',
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
            'qty.integer' => '订阅数量必须为整数',
            'qty.min' => '订阅数量最少为1',
            'qty.max' => '订阅数量最多为99',
        ];
    }
}
