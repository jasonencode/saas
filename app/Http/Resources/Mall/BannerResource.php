<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'banner_id' => $this->resource->id,
            'title' => $this->resource->title,
            'cover' => $this->resource->cover_url,
            'jump' => $this->resource->jump,
        ];
    }
}
