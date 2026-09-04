<?php

namespace App\Http\Resources\Chain;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChainAddressResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'chain_address_id' => $this->resource->id,
            'address' => $this->resource->address,
            'name' => $this->resource->name,
            'network' => NetworkResource::make($this->whenLoaded('network')),
        ];
    }
}
