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
            'address_id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'province' => new RegionResource($this->province),
            'city' => new RegionResource($this->city),
            'district' => new RegionResource($this->district),
            'address' => $this->address,
            'is_default' => $this->is_default,
        ];
    }
}
