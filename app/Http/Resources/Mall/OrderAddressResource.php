<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'mobile' => $this->resource->mobile,
            'address' => $this->resource->address,
            'region' => [
                'province_id' => $this->resource->province_id,
                'city_id' => $this->resource->city_id,
                'district_id' => $this->resource->district_id,
            ],
        ];
    }
}
