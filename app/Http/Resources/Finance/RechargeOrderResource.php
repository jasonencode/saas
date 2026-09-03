<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RechargeOrderResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->resource->id,
            'order_no' => $this->resource->no,
            'type' => $this->resource->type?->value,
            'type_label' => $this->resource->type?->getLabel(),
            'amount' => $this->resource->amount,
            'received_amount' => $this->resource->received_amount,
            'gateway' => $this->resource->gateway?->value,
            'gateway_label' => $this->resource->gateway?->getLabel(),
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'payment_no' => $this->resource->payment_no,
            'remark' => $this->resource->remark,
            'paid_at' => $this->resource->paid_at,
            'completed_at' => $this->resource->completed_at,
            'expired_at' => $this->resource->expired_at,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
