<?php

namespace App\Http\Requests\Campaign;

use App\Http\Requests\BaseFormRequest;

class CouponMineRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_used' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
