<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'order_id' => $this->resource->id,
            'order_no' => $this->resource->no,
            'amount' => $this->resource->amount,
            'gateway' => $this->resource->gateway?->value,
            'gateway_label' => $this->resource->gateway?->getLabel(),
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'paymentable_type' => $this->resource->paymentable_type,
            'paymentable_id' => $this->resource->paymentable_id,
            'remark' => null,
            'paid_at' => $this->resource->paid_at,
            'expired_at' => $this->resource->expired_at,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
