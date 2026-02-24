<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public bool $preserveKeys = true;

    public function toArray(Request $request): array
    {
        return [
            'address_id' => $this->resource->id,
            'name' => $this->resource->name,
            'mobile' => $this->resource->mobile,
            'province' => new RegionResource($this->resource->province),
            'city' => new RegionResource($this->resource->city),
            'district' => new RegionResource($this->resource->district),
            'address' => $this->resource->address,
            'is_default' => $this->resource->is_default,
        ];
    }
}
