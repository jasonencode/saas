<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;

class ShipReturnRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'express_id' => [
                'required',
                'integer',
                'exists:expresses,id',
            ],
            'express_no' => [
                'required',
                'string',
                'max:32',
            ],
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
            'express_id.required' => '物流公司必须选择',
            'express_id.exists' => '物流公司不存在',
            'express_no.required' => '物流单号必须填写',
            'express_no.max' => '物流单号最长32个字符',
        ];
    }
}
