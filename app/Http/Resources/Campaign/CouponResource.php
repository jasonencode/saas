<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\EnumResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
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
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
            'expired_type' => EnumResource::make($this->resource->expired_type),
            'days' => $this->when($this->resource->expired_type->value === 'receive', $this->resource->days),
            'status' => $this->resource->status,
            'state' => $this->getStateLabel(),
            'can_be_used' => $this->canBeUsed(),
            'user_state' => $this->getUserState(),
        ];
    }

    /**
     * 获取当前用户的领取状态
     *
     * @return string|null 未登录或未领取返回 null，已领取/已使用/已过期返回对应状态
     */
    protected function getUserState(): ?string
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $pivot = $this->resource->users()
            ->wherePivot('user_id', $user->getKey())
            ->first()?->pivot;

        if ($pivot === null) {
            return null;
        }

        if ($pivot->is_used) {
            return 'used';
        }

        if ($pivot->expired_at && $pivot->expired_at->isPast()) {
            return 'expired';
        }

        return 'claimed';
    }

    /**
     * 获取状态标签
     */
    protected function getStateLabel(): string
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
