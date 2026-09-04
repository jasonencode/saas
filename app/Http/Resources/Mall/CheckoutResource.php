<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\User\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => CartItemResource::collection($this->resource->get('items')),
            'addresses' => AddressResource::collection($this->resource->get('addresses')),
            'address' => $this->resource->get('address')
                ? AddressResource::make($this->resource->get('address'))
                : null,
            'total_amount' => $this->resource->get('total_amount'),
            'freight' => $this->resource->get('freight'),
            'payable_amount' => $this->resource->get('payable_amount'),
        ];
    }
}
