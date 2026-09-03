<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawOrderResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->resource->id,
            'order_no' => $this->resource->no,
            'amount' => $this->resource->amount,
            'fee' => $this->resource->fee,
            'actual_amount' => $this->resource->actual_amount,
            'gateway' => $this->resource->gateway?->value,
            'gateway_label' => $this->resource->gateway?->getLabel(),
            'account_info' => $this->resource->account_info,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'remark' => $this->resource->remark,
            'reject_reason' => $this->resource->reject_reason,
            'reviewer_id' => $this->resource->reviewer_id,
            'reviewed_at' => $this->resource->reviewed_at,
            'paid_at' => $this->resource->paid_at,
            'payment_no' => $this->resource->payment_no,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
