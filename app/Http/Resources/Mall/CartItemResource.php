<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->resource->id,
            'product' => [
                'product_id' => $this->resource->product_id,
                'name' => $this->resource->product?->name,
                'cover' => $this->resource->product?->cover_url,
            ],
            'sku' => [
                'sku_id' => $this->resource->sku_id,
                'name' => $this->resource->sku?->name,
                'specifications' => $this->resource->sku?->specifications ?? [],
            ],
            'qty' => $this->resource->qty,
            'price' => $this->resource->price_at_add,
            'sub_total' => $this->resource->sub_total,
            'selected' => $this->resource->selected,
            'is_available' => $this->resource->isAvailable(),
        ];
    }
}
