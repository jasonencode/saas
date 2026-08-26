<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 退款资源
 */
class PaymentRefundResource extends JsonResource
{
    use HasDateTimeFormat;

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
            'refunded_at' => $this->formatDateTime($this->resource->refunded_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
