<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'content_id' => $this->id,
            'title' => $this->title,
            'created_at' => $this->created_at,
        ];
    }
}