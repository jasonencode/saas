<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionTwoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'region_id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'level' => $this->level,
            'children' => RegionResource::collection($this->children),
        ];
    }
}