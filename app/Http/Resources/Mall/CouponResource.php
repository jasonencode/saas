<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'coupon_id' => $this->resource->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->getLabel(),
            ],
            'discount_amount' => $this->discount_amount,
            'threshold_amount' => $this->threshold_amount,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'used_count' => $this->usage_count,
            'start_at' => $this->start_at?->toDateTimeString(),
            'end_at' => $this->end_at?->toDateTimeString(),
            'expired_type' => [
                'value' => $this->expired_type->value,
                'label' => $this->expired_type->getLabel(),
            ],
            'days' => $this->when($this->expired_type->value === 'receive', $this->days),
            'status' => [
                'value' => $this->status,
                'label' => $this->getStatusLabel(),
            ],
            'can_be_used' => $this->canBeUsed(),
        ];
    }

    protected function getStatusLabel(): string
    {
        if (! $this->status) {
            return '已禁用';
        }

        if ($this->start_at && now()->isBefore($this->start_at)) {
            return '未开始';
        }

        if ($this->end_at && now()->isAfter($this->end_at)) {
            return '已过期';
        }

        return '使用中';
    }
}
