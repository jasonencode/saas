<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedpackResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'redpack_id' => $this->resource->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_at' => $this->formatDateTime($this->start_at),
            'end_at' => $this->formatDateTime($this->end_at),
            'status' => (bool) $this->status,
            'status_label' => $this->getStatusLabel(),
            'codes_count' => $this->whenCounted('codes'),
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
