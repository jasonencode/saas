<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'lottery_id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'cover' => $this->resource->cover,
            'draw_mode' => $this->resource->draw_mode->value,
            'draw_mode_label' => $this->resource->draw_mode->getLabel(),
            'free_draws_per_day' => $this->resource->free_draws_per_day,
            'points_per_draw' => (float) $this->resource->points_per_draw,
            'max_draws_per_user' => $this->resource->max_draws_per_user,
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
            'status' => (bool) $this->resource->status,
            'status_label' => $this->getStatusLabel(),
            'prizes_count' => $this->whenCounted('prizes'),
            'prizes' => LotteryPrizeResource::collection($this->whenLoaded('prizes')),
            'created_at' => $this->resource->created_at,
        ];
    }

    /**
     * 获取状态标签
     */
    protected function getStatusLabel(): string
    {
        if (!$this->resource->status) {
            return '已禁用';
        }

        if ($this->resource->start_at && now()->isBefore($this->resource->start_at)) {
            return '未开始';
        }

        if ($this->resource->end_at && now()->isAfter($this->resource->end_at)) {
            return '已过期';
        }

        return '进行中';
    }
}
