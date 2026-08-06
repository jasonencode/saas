<?php

namespace App\Http\Resources\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'content_id' => $this->resource->id,
            'title' => $this->resource->title,
            'sub_title' => $this->resource->sub_title,
            'description' => $this->resource->description,
            'author' => $this->resource->author,
            'source' => $this->resource->source,
            'content' => $this->resource->content,
            'cover' => $this->resource->cover_url,
            'views' => $this->resource->views,
            'status' => $this->resource->status,
            'tags' => $this->when($this->resource->relationLoaded('tags'), fn () => $this->resource->tags->map(fn ($tag) => [
                'tag_id' => $tag->id,
                'name' => $tag->name,
            ])),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
