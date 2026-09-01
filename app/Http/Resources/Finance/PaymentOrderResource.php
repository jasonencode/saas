<?php

namespace App\Http\Resources\Finance;

use App\Services\Finance\PaymentableResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentOrderResource extends JsonResource
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
            'gateway' => $this->resource->gateway?->value,
            'gateway_label' => $this->resource->gateway?->getLabel(),
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'paymentable_type' => PaymentableResolver::keyFor($this->resource->paymentable_type),
            'paymentable_id' => $this->resource->paymentable_id,
            'paid_at' => $this->resource->paid_at,
            'expired_at' => $this->resource->expired_at,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
