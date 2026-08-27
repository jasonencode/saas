<?php

namespace App\Http\Requests\Campaign;

use App\Enums\Campaign\CouponType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CouponIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(CouponType::class)],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['sometimes', 'numeric', 'min:0'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
