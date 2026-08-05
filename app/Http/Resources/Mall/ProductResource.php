<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'goods_id' => $this->resource->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover' => $this->cover_url,
            'pictures' => $this->picture_urls,
            'materials' => $this->material_urls,
            'price' => $this->price,
            'origin_price' => $this->origin_price,
            'total_stock' => $this->total_stock,
            'views' => $this->views,
            'total_sale' => $this->total_sale,
            'store' => $this->when($this->storeConfigure, new StoreConfigureResource($this->storeConfigure), null),
            'brand' => $this->when($this->brand, new BrandResource($this->brand), null),
            'can_cart' => $this->can_cart,
            'skus' => $this->skus->map(fn ($sku) => [
                'sku_id' => $sku->id,
                'name' => $sku->name,
                'code' => $sku->code,
                'cover' => $sku->cover_url,
                'price' => $sku->price,
                'origin_price' => $sku->origin_price,
                'stock' => $sku->stock,
                'sale' => $sku->sale,
            ]),
        ];
    }
}
