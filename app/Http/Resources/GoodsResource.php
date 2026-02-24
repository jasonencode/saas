<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'goods_id' => $this->resource->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover' => $this->cover_url,
            'pictures' => $this->picture_urls,
            'price' => $this->price,
            'origin_price' => $this->origin_price,
            'stocks' => $this->stocks,
            'views' => $this->views,
            'sales' => $this->sales,
            'brand' => $this->when($this->brand, new BrandResource($this->brand), null),
            'can_cart' => $this->can_cart,
            'materials' => $this->material_urls,
        ];
    }
}