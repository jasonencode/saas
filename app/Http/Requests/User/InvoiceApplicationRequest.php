<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;

class InvoiceApplicationRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'invoice_title_id' => 'required|exists:invoice_titles,id',
            'reason' => 'required|string|max:255',
            // 开票按订单推导租户，order_ids 为空会在推导时得到 null 租户导致 TypeError
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|min:1|exists:orders,id',
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
            'invoice_title_id.required' => '发票抬头必须选择',
            'invoice_title_id.exists' => '发票抬头不存在',
            'reason.required' => '开票原因必须填写',
            'reason.max' => '开票原因最多255个字符',
            'order_ids.required' => '关联订单不能为空',
            'order_ids.array' => '关联订单必须是数组',
            'order_ids.min' => '关联订单至少选择一个',
            'order_ids.*.integer' => '订单ID必须是整数',
            'order_ids.*.exists' => '订单不存在',
        ];
    }
}
