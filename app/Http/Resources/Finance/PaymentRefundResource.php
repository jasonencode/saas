<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 退款资源
 */
class PaymentRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'refund_id' => $this->resource->id,
            'no' => $this->resource->no,
            'amount' => $this->resource->amount,
            'reason' => $this->resource->reason,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'refunded_at' => $this->resource->refunded_at?->toDateTimeString(),
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
