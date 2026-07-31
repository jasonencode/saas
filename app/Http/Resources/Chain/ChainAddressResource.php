<?php

namespace App\Http\Resources\Chain;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChainAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'chain_address_id' => $this->resource->id,
            'address' => $this->address,
            'name' => $this->name,
            'network' => new NetworkResource($this->whenLoaded('network')),
        ];
    }
}
