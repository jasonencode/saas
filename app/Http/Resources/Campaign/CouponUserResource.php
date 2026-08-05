<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponUserResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'is_used' => $this->resource->is_used,
            'expired_at' => $this->resource->expired_at?->toDateTimeString(),
            'used_at' => $this->resource->used_at?->toDateTimeString(),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
            'can_be_used' => $this->canBeUsed(),
        ];
    }

    /**
     * 是否可使用
     */
    protected function canBeUsed(): bool
    {
        if ($this->resource->is_used) {
            return false;
        }

        if ($this->resource->expired_at && now()->isAfter($this->resource->expired_at)) {
            return false;
        }

        return (bool) $this->resource->coupon?->isValid();
    }
}
