<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\User\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'items' => CartItemResource::collection($this->resource->items),
            'addresses' => AddressResource::collection($this->resource->addresses),
            'address' => $this->resource->address
                ? new AddressResource($this->resource->address)
                : null,
            'total_amount' => $this->resource->total_amount,
            'freight' => $this->resource->freight,
            'payable_amount' => $this->resource->payable_amount,
        ];
    }
}
