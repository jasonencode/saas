<?php

namespace App\Http\Resources\Mall;

use App\Services\Mall\ProductDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @param  mixed  $resource  购物车商品
     * @param  int|null  $percent  身份折扣百分比，为空表示无折扣
     */
    public function __construct($resource, private readonly ?int $percent = null)
    {
        parent::__construct($resource);
    }

    /**
     * 获取划线原价（当前 SKU 销售价）
     */
    public function originalPrice(): string
    {
        return $this->resource->sku?->getOrderablePrice() ?? '0.00';
    }

    /**
     * 获取实时折后单价（无折扣时等于原价）
     */
    public function discountedPrice(): string
    {
        if ($this->percent === null) {
            return $this->originalPrice();
        }

        return service(ProductDiscountService::class)->applyPercent($this->originalPrice(), $this->percent);
    }

    /**
     * 获取折后小计（折后单价 × 数量）
     */
    public function subTotal(): string
    {
        return bcmul($this->discountedPrice(), (string) $this->resource->qty, 2);
    }

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->resource->id,
            'product' => [
                'product_id' => $this->resource->product_id,
                'name' => $this->resource->product?->name,
                'cover' => $this->resource->product?->cover_url,
                'fulfillment_types' => $this->resource->product?->fulfillment_type ?? [],
            ],
            'sku' => [
                'sku_id' => $this->resource->sku_id,
                'name' => $this->resource->sku?->name,
            ],
            'qty' => $this->resource->qty,
            // 实时折后价，金额以实时计算为准；price_at_add 仅作加购快照，不作为展示依据
            'price' => $this->discountedPrice(),
            'original_price' => $this->originalPrice(),
            'sub_total' => $this->subTotal(),
            'is_available' => $this->resource->isAvailable(),
        ];
    }
}
