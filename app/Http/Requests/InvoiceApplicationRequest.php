<?php

namespace App\Http\Requests;

class InvoiceApplicationRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'invoice_title_id' => 'required|exists:invoice_titles,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'remark' => 'nullable|string',
            'order_ids' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_title_id.required' => '发票抬头必须选择',
            'invoice_title_id.exists' => '发票抬头不存在',
            'amount.required' => '开票金额必须填写',
            'amount.numeric' => '开票金额必须是数字',
            'amount.min' => '开票金额不能小于0.01',
            'reason.required' => '开票原因必须填写',
            'reason.max' => '开票原因最多255个字符',
            'order_ids.array' => '关联订单必须是数组',
        ];
    }
}
