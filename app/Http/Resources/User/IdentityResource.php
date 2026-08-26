<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'identity_id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'cover' => $this->resource->cover_url,
            'price' => $this->resource->price,
            'days' => $this->resource->days,
            'can_subscribe' => $this->resource->can_subscribe,
            'is_unique' => $this->resource->is_unique,
            'conditions' => $this->resource->conditions,
            'rules' => $this->resource->rules,
            'pivot' => $this->when($this->resource->pivot, fn () => [
                'start_at' => $this->formatDateTime($this->resource->pivot->start_at),
                'end_at' => $this->formatDateTime($this->resource->pivot->end_at),
                'serial' => $this->resource->pivot->serial_no,
            ]),
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
