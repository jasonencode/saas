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
            'name' => $this->resource->name,
            'cover' => $this->resource->cover_url,
            'price' => $this->resource->price,
            'origin_price' => $this->resource->origin_price,
            'total_stock' => $this->resource->total_stock,
            'total_sale' => $this->resource->total_sale,
            'can_cart' => $this->resource->can_cart,
            'views' => $this->resource->views,
            'brand' => $this->when($this->resource->brand, fn () => [
                'brand_id' => $this->resource->brand->id,
                'name' => $this->resource->brand->name,
            ]),
            'category' => $this->when($this->resource->category, fn () => [
                'category_id' => $this->resource->category->id,
                'name' => $this->resource->category->name,
            ]),
        ];
    }
}
