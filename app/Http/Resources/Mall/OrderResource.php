<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    use HasDateTimeFormat;

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
            'fulfillment_type' => [
                'value' => $this->resource->fulfillment_type->value,
                'label' => $this->resource->fulfillment_type->getLabel(),
            ],
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
            'expired_at' => $this->formatDateTime($this->resource->expired_at),
            'paid_at' => $this->formatDateTime($this->resource->paid_at),
            'signed_at' => $this->formatDateTime($this->resource->signed_at),
            'verified_at' => $this->formatDateTime($this->resource->verified_at),
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
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
