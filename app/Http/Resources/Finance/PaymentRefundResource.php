<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\EnumResource;
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
            'status' => EnumResource::make($this->resource->status),
            'failed_reason' => $this->resource->failed_reason,
            'refunded_at' => $this->resource->refunded_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
