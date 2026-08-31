<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListItemResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'goods_id' => $this->resource->id,
            'name' => $this->name,
            'cover' => $this->cover_url,
            'price' => $this->price,
            'origin_price' => $this->origin_price,
            'total_stock' => $this->total_stock,
            'total_sale' => $this->total_sale,
            'can_cart' => $this->can_cart,
            'views' => $this->views,
            'store' => $this->when($this->storeConfigure, new StoreConfigureResource($this->storeConfigure), null),
            'brand' => $this->when($this->brand, fn () => [
                'brand_id' => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'category' => $this->when($this->category, fn () => [
                'category_id' => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
