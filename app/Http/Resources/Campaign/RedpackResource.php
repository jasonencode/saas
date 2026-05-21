<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedpackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'redpack_id' => $this->resource->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->start_at?->toDateTimeString(),
            'end_at' => $this->end_at?->toDateTimeString(),
            'status' => (bool) $this->status,
            'status_label' => $this->getStatusLabel(),
            'codes_count' => $this->when($this->relationLoaded('codesCount'), fn () => $this->codesCount),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    protected function getStatusLabel(): string
    {
        if (!$this->status) {
            return '已禁用';
        }

        if ($this->start_at && now()->isBefore($this->start_at)) {
            return '未开始';
        }

        if ($this->end_at && now()->isAfter($this->end_at)) {
            return '已过期';
        }

        return '进行中';
    }
}
