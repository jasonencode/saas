<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderShippingResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'shipping_id' => $this->resource->id,
            'express' => $this->resource->express ? [
                'express_id' => $this->resource->express->id,
                'name' => $this->resource->express->name,
            ] : null,
            'express_no' => $this->resource->express_no,
            'items' => OrderItemResource::collection($this->resource->items),
            'address' => OrderAddressResource::make($this->resource),
            'delivery_at' => $this->formatDateTime($this->resource->delivery_at),
            'sign_at' => $this->formatDateTime($this->resource->sign_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
