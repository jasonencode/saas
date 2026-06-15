<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreConfigureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tenant_id' => $this->resource->tenant_id,
            'store_name' => $this->resource->store_name,
            'store_description' => $this->resource->store_description,
            'logo' => $this->resource->cover_url,
            'phone' => $this->resource->phone,
            'contactor' => $this->resource->contactor,
            'address' => $this->resource->full_address,
        ];
    }
}
