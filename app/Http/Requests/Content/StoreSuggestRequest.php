<?php

namespace App\Http\Requests\Content;

use App\Enums\Content\SuggestType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSuggestRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Enum|string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(SuggestType::class)],
            'content' => ['required', 'string', 'min:5', 'max:500'],
            'contact' => ['nullable', 'string', 'max:100'],
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
            'type.required' => '请选择反馈类型',
            'type.in' => '无效的反馈类型',
            'content.required' => '请填写反馈内容',
            'content.min' => '反馈内容至少:min个字',
            'content.max' => '反馈内容不能超过:max个字',
            'contact.max' => '联系方式不能超过:max个字',
        ];
    }
}
