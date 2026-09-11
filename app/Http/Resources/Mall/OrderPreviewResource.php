<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\User\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPreviewResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'item' => OrderPreviewItemResource::make($this->resource->item),
            'addresses' => AddressResource::collection($this->resource->addresses),
            'address' => $this->resource->address
                ? AddressResource::make($this->resource->address)
                : null,
            // 金额口径：goods_amount（商品总额）+ freight（运费）− coupon_discount（券抵扣）= payable_amount
            'goods_amount' => $this->resource->goods_amount,
            'coupon_discount' => $this->resource->coupon_discount,
            'freight' => $this->resource->freight,
            'payable_amount' => $this->resource->payable_amount,
        ];
    }
}
