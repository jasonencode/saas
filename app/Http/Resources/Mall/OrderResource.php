<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\EnumResource;
use App\Http\Resources\User\UserProfileResource;
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
            'status' => EnumResource::make($this->resource->status),
            'fulfillment_type' => EnumResource::make($this->resource->fulfillment_type),
            'total_amount' => $this->resource->total_amount,
            'amount' => $this->resource->amount,
            'freight' => $this->resource->freight,
            'items_quantity' => $this->resource->items_quantity,
            'items' => OrderItemResource::collection($this->resource->items),
            'address' => OrderAddressResource::make($this->resource->address),
            'user' => UserProfileResource::make($this->resource->user),
            'store' => StoreConfigureResource::make($this->resource->tenant->storeConfigure),
            'after_sales' => AfterSalesResource::make($this->resource),
            'expired_at' => $this->resource->expired_at,
            'paid_at' => $this->resource->paid_at,
            'signed_at' => $this->resource->signed_at,
            'verified_at' => $this->resource->verified_at,
            'pickup_code' => $this->resource->pickup_code,
            'pickup_point' => $this->when($this->resource->pickupPoint, PickupPointResource::make($this->resource->pickupPoint), null),
            'created_at' => $this->resource->created_at,
        ];
    }
}
