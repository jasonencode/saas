<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'refund_id' => $this->resource->id,
            'no' => $this->resource->no,
            'order' => [
                'order_id' => $this->resource->order_id,
                'no' => $this->resource->order?->no,
            ],
            'status' => EnumResource::make($this->resource->status),
            'type' => EnumResource::make($this->resource->type),
            'reason' => $this->resource->reason ? EnumResource::make($this->resource->reason) : null,
            'reason_detail' => $this->resource->reason_detail,
            'goods_amount' => $this->resource->goods_amount,
            'freight_amount' => $this->resource->freight_amount,
            'total' => $this->resource->total,
            'store' => StoreConfigureResource::make($this->resource->order?->tenant?->storeConfigure),
            'items' => RefundItemResource::collection($this->resource->items),
            'express' => RefundExpressResource::make($this->resource->express),
            'logs' => RefundLogResource::collection($this->whenLoaded('logs')),
            'approved_at' => $this->formatDateTime($this->resource->approved_at),
            'refund_at' => $this->formatDateTime($this->resource->refund_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
