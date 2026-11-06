<?php

namespace App\Http\Resources\Campaign;

use App\Models\Campaign\SeckillActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 秒杀活动
 *
 * @mixin SeckillActivity
 */
class SeckillActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'activity_id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'cover' => $this->resource->cover,
            'sessions' => SeckillSessionResource::collection($this->whenLoaded('sessions')),
        ];
    }
}
