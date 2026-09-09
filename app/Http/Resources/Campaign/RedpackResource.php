<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedpackResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'redpack_id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
            'status' => (bool) $this->resource->status,
            'status_label' => $this->getStatusLabel(),
            'codes_count' => $this->whenCounted('codes'),
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
