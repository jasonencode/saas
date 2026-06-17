<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class RenewIdentityRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'qty' => 'sometimes|integer|min:1|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'qty.integer' => '续期数量必须为整数',
            'qty.min' => '续期数量最少为1',
            'qty.max' => '续期数量最多为99',
        ];
    }
}
