<?php

namespace App\Http\Resources\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'tag_id' => $this->resource->id,
            'name' => $this->resource->name,
            'contents_count' => $this->resource->contents_count ?? 0,
        ];
    }
}
