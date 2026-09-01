<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\EnumResource;
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
            'status' => EnumResource::make($this->resource->status),
            'fulfillment_type' => EnumResource::make($this->resource->fulfillment_type),
            'total_amount' => $this->resource->total_amount,
            'amount' => $this->resource->amount,
            'freight' => $this->resource->freight,
            'items_quantity' => $this->resource->items_quantity,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'address' => OrderAddressResource::make($this->whenLoaded('address')),
            'user' => [
                'user_id' => $this->resource->user_id,
                'username' => $this->resource->user?->username,
            ],
            'store' => StoreConfigureResource::make($this->resource->tenant->storeConfigure),
            'expired_at' => $this->formatDateTime($this->resource->expired_at),
            'paid_at' => $this->formatDateTime($this->resource->paid_at),
            'signed_at' => $this->formatDateTime($this->resource->signed_at),
            'verified_at' => $this->formatDateTime($this->resource->verified_at),
            'pickup_code' => $this->resource->pickup_code,
            'pickup_point' => $this->when($this->resource->pickupPoint, PickupPointResource::make($this->resource->pickupPoint), null),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
