<?php

namespace App\Http\Requests\Content;

use App\Enums\Content\SuggestStatus;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexSuggestRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Enum|string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(SuggestStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->status === '') {
            $this->request->remove('status');
        }
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => '无效的状态值',
            'page.integer' => '页码格式不正确',
            'page.min' => '页码最小为:min',
            'per_page.integer' => '每页条数格式不正确',
            'per_page.min' => '每页条数最小为:min',
            'per_page.max' => '每页条数最大为:max',
        ];
    }
}
