<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public bool $preserveKeys = true;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'address_id' => $this->resource->id,
            'name' => $this->resource->name,
            'mobile' => $this->resource->mobile,
            'province' => RegionResource::make($this->resource->province),
            'city' => RegionResource::make($this->resource->city),
            'district' => RegionResource::make($this->resource->district),
            'address' => $this->resource->address,
            'is_default' => $this->resource->is_default,
        ];
    }
}
