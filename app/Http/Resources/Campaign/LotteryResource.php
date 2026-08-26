<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
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
            'start_at' => $this->formatDateTime($this->start_at),
            'end_at' => $this->formatDateTime($this->end_at),
            'status' => (bool) $this->status,
            'status_label' => $this->getStatusLabel(),
            'prizes_count' => $this->whenCounted('prizes'),
            'prizes' => LotteryPrizeResource::collection($this->whenLoaded('prizes')),
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }

    /**
     * 获取状态标签
     */
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
