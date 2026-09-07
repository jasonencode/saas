<?php

namespace App\Http\Requests\Mall;

use App\Enums\Mall\RefundScope;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class RefundIndexRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'scope' => [
                'nullable',
                Rule::enum(RefundScope::class),
            ],
            'keyword' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'scope' => '筛选参数不正确',
            'keyword.max' => '搜索关键词最多100个字符',
        ];
    }
}
