<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->resource->id,
            'no' => $this->resource->no,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'fulfillment_type' => $this->resource->fulfillment_type
                ? [
                    'value' => $this->resource->fulfillment_type->value,
                    'label' => $this->resource->fulfillment_type->getLabel(),
                ]
                : null,
            'total_amount' => $this->resource->total_amount,
            'amount' => $this->resource->amount,
            'freight' => $this->resource->freight,
            'items_quantity' => $this->resource->items_quantity,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'address' => new OrderAddressResource($this->whenLoaded('address')),
            'user' => [
                'user_id' => $this->resource->user_id,
                'username' => $this->resource->user?->username,
            ],
            'expired_at' => $this->resource->expired_at?->toDateTimeString(),
            'paid_at' => $this->resource->paid_at?->toDateTimeString(),
            'signed_at' => $this->resource->signed_at?->toDateTimeString(),
            'verified_at' => $this->resource->verified_at?->toDateTimeString(),
            'pickup_code' => $this->resource->pickup_code,
            'pickup_point' => $this->resource->pickupPoint
                ? [
                    'pickup_point_id' => $this->resource->pickupPoint->id,
                    'name' => $this->resource->pickupPoint->name,
                    'address' => $this->resource->pickupPoint->full_address,
                    'contact' => $this->resource->pickupPoint->contact,
                    'phone' => $this->resource->pickupPoint->phone,
                ]
                : null,
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}
