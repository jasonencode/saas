<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'identity_id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'cover' => $this->resource->cover,
            'price' => $this->resource->price,
            'days' => $this->resource->days,
            'can_subscribe' => $this->resource->can_subscribe,
            'is_unique' => $this->resource->is_unique,
            'conditions' => $this->resource->conditions,
            'rules' => $this->resource->rules,
            'pivot' => $this->when($this->resource->pivot, fn () => [
                'start_at' => $this->resource->pivot->start_at?->toDateTimeString(),
                'end_at' => $this->resource->pivot->end_at?->toDateTimeString(),
                'serial' => $this->resource->pivot->serial_no,
            ]),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}
