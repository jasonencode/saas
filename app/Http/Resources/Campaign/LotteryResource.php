<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'lottery_id' => $this->resource->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover' => $this->cover,
            'draw_mode' => $this->draw_mode->value,
            'draw_mode_label' => $this->draw_mode->getLabel(),
            'free_draws_per_day' => $this->free_draws_per_day,
            'points_per_draw' => (float) $this->points_per_draw,
            'max_draws_per_user' => $this->max_draws_per_user,
            'start_at' => $this->start_at?->toDateTimeString(),
            'end_at' => $this->end_at?->toDateTimeString(),
            'status' => (bool) $this->status,
            'status_label' => $this->getStatusLabel(),
            'prizes_count' => $this->whenCounted('prizes'),
            'prizes' => LotteryPrizeResource::collection($this->whenLoaded('prizes')),
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
