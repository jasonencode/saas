<?php

namespace App\Http\Resources\Contents;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->resource->id,
            'level' => $this->resource->level,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'cover' => $this->resource->cover_url,
            'children' => $this->when($this->resource->children()->ofEnabled()->exists(), function () {
                return CategoryResource::collection($this->resource->children()->ofEnabled()->get());
            }),
        ];
    }
}