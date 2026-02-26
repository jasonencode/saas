<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'region_id' => $this->resource->id,
            'parent_id' => $this->resource->parent_id,
            'name' => $this->resource->name,
            'level' => $this->resource->level,
        ];
    }
}
