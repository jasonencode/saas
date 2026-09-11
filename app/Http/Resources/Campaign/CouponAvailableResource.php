<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\EnumResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 结算可用券资源
 *
 * 承载「用户持券实例 + 对当前结算商品的预估抵扣」；
 * 不可用券同样返回，并以 inapplicable_reason 说明原因，供前端置灰展示。
 */
class CouponAvailableResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        $couponUser = $this->resource->get('coupon_user');
        $coupon = $couponUser->coupon;

        return [
            'coupon_user_id' => $couponUser->getKey(),
            'name' => $coupon->name,
            'type' => EnumResource::make($coupon->type),
            'min_amount' => $coupon->min_amount,
            'expired_at' => $couponUser->expired_at,
            'applicable' => (bool) $this->resource->get('applicable'),
            'discount_preview' => $this->resource->get('discount_preview'),
            'base_amount' => $this->resource->get('base_amount'),
            'inapplicable_reason' => $this->resource->get('inapplicable_reason'),
        ];
    }
}
