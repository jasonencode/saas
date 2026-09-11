<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\User\AddressResource;
use App\Models\Mall\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        // 身份折扣百分比：商品 ID => percent（由控制器批量查得，防 N+1）
        $percentMap = $this->resource->get('percent_map', []);

        return [
            // 逐个显式构造并传入 percent：CartItemResource::collection() 会经
            // Collection::mapInto() 把集合键当作第二个构造参数传入，导致 percent 取到下标
            'items' => $this->resource->get('items')->map(
                fn (CartItem $item) => new CartItemResource($item, $percentMap[$item->product_id] ?? null)
            )->values(),
            'addresses' => AddressResource::collection($this->resource->get('addresses')),
            'address' => $this->resource->get('address')
                ? AddressResource::make($this->resource->get('address'))
                : null,
            // 金额口径：goods_amount（商品总额）+ freight（运费）− coupon_discount（券抵扣）= payable_amount
            'goods_amount' => $this->resource->get('goods_amount'),
            'coupon_discount' => $this->resource->get('coupon_discount'),
            'freight' => $this->resource->get('freight'),
            'payable_amount' => $this->resource->get('payable_amount'),
        ];
    }
}
