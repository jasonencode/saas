<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
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
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'type' => [
                'value' => $this->resource->type->value,
                'label' => $this->resource->type->getLabel(),
            ],
            'reason' => [
                'value' => $this->resource->reason?->value,
                'label' => $this->resource->reason?->getLabel(),
            ],
            'reason_detail' => $this->resource->reason_detail,
            'goods_amount' => $this->resource->goods_amount,
            'freight_amount' => $this->resource->freight_amount,
            'total' => $this->resource->total,
            'items' => RefundItemResource::collection($this->whenLoaded('items')),
            'express' => new RefundExpressResource($this->whenLoaded('express')),
            'logs' => RefundLogResource::collection($this->whenLoaded('logs')),
            'approved_at' => $this->resource->approved_at?->toDateTimeString(),
            'refund_at' => $this->resource->refund_at?->toDateTimeString(),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}
