<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderShippingResource extends JsonResource
{
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
            'items' => $this->resource->items->map(fn ($item) => [
                'item_id' => $item->id,
                'product' => [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'cover' => $item->product?->cover_url,
                ],
                'sku' => [
                    'sku_id' => $item->product_sku_id,
                    'name' => $item->sku?->name,
                ],
                'qty' => $item->qty,
            ]),
            'address' => [
                'name' => $this->resource->name,
                'mobile' => $this->resource->mobile,
                'address' => $this->resource->address,
                'region' => [
                    'province_id' => $this->resource->province_id,
                    'city_id' => $this->resource->city_id,
                    'district_id' => $this->resource->district_id,
                ],
            ],
            'delivery_at' => $this->resource->delivery_at?->toDateTimeString(),
            'sign_at' => $this->resource->sign_at?->toDateTimeString(),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}
