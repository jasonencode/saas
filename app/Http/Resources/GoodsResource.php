<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\ProductContentType;

class GoodsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'goods_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover' => $this->cover_url,
            'pictures' => $this->picture_urls,
            'price' => $this->price,
            'origin_price' => $this->origin_price,
            'stocks' => $this->stocks,
            'views' => $this->views,
            'sales' => $this->sales,
            'store' => [
                'store_id' => $this->store?->id,
                'name' => $this->store?->name,
                'cover' => $this->store?->cover,
                'is_self' => $this->store?->is_self,
            ],
            'brand' => $this->when($this->brand, new BrandResource($this->brand), null),
            'can_cart' => $this->can_cart,
            'content_type' => $this->content_type,
            'rich_text' => $this->when(
                $this->content_type === ProductContentType::RichText,
                $this->content,
                null
            ),
            'materials' => $this->when(
                $this->content_type === ProductContentType::Material,
                $this->material_urls,
                null
            ),
        ];
    }
}