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
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'cover' => $this->resource->cover_url,
            'pictures' => $this->resource->picture_urls,
            'materials' => $this->resource->material_urls,
            'price' => $this->resource->price,
            'origin_price' => $this->resource->origin_price,
            'total_stock' => $this->resource->total_stock,
            'views' => $this->resource->views,
            'total_sale' => $this->resource->total_sale,
            'store' => $this->when($this->resource->storeConfigure, StoreConfigureResource::make($this->resource->storeConfigure), null),
            'brand' => $this->when($this->resource->brand, BrandResource::make($this->resource->brand), null),
            'tags' => $this->when($this->resource->relationLoaded('tags'), fn () => $this->resource->tags->map(fn ($tag) => [
                'tag_id' => $tag->id,
                'name' => $tag->name,
            ])),
            'can_cart' => $this->resource->can_cart,
            'fulfillment_types' => $this->resource->fulfillment_type ?? [],
            'skus' => $this->resource->skus->map(fn ($sku) => [
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
