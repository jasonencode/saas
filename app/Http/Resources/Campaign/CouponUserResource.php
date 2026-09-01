<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\Traits\HasDateTimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponUserResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'coupon' => CouponResource::make($this->whenLoaded('coupon')),
            'is_used' => $this->resource->is_used,
            'expired_at' => $this->formatDateTime($this->resource->expired_at),
            'used_at' => $this->formatDateTime($this->resource->used_at),
            'created_at' => $this->formatDateTime($this->resource->created_at),
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
