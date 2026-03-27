<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cart_id' => $this->resource->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'total_qty' => $this->resource->total_qty,
            'total_amount' => $this->resource->total_amount,
            'is_expired' => $this->resource->isExpired(),
        ];
    }
}
