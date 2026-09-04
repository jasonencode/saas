<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'coupon_id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'type' => EnumResource::make($this->resource->type),
            'discount_amount' => $this->resource->value,
            'min_amount' => $this->resource->min_amount,
            'usage_limit' => $this->resource->usage_limit,
            'usage_limit_per_user' => $this->resource->usage_limit_per_user,
            'start_at' => $this->formatDateTime($this->resource->start_at),
            'end_at' => $this->formatDateTime($this->resource->end_at),
            'expired_type' => EnumResource::make($this->resource->expired_type),
            'days' => $this->when($this->resource->expired_type->value === 'receive', $this->resource->days),
            'status' => [
                'value' => $this->resource->status,
                'label' => $this->getStatusLabel(),
            ],
            'can_be_used' => $this->canBeUsed(),
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

        return '使用中';
    }
}
