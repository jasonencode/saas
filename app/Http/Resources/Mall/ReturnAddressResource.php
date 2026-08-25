<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnAddressResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'return_address_id' => $this->resource->id,
            'name' => $this->resource->name,
            'contact' => $this->resource->contact,
            'phone' => $this->resource->phone,
            'address' => $this->resource->address,
            'full_address' => $this->resource->full_address,
            'region' => [
                'province_id' => $this->resource->province_id,
                'city_id' => $this->resource->city_id,
                'district_id' => $this->resource->district_id,
            ],
            'is_default' => $this->resource->is_default,
            'sort' => $this->resource->sort,
        ];
    }
}
