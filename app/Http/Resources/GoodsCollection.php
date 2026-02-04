<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;

class GoodsCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function($item) {
                return [
                    'goods_id' => $item->id,
                    'name' => $item->name,
                    'cover' => $item->cover_url,
                    'price' => $item->price,
                    'origin_price' => $item->origin_price,
                    'profit' => $item->profit,
                    'views' => $item->views,
                    'sales' => $item->sales,
                    'store' => [
                        'store_id' => $item->store?->id,
                        'name' => $item->store?->name,
                        'cover' => $item->store?->cover_url,
                        'is_self' => $item->store?->is_self,
                    ],
                    'brand' => $this->when($item->brand, new BrandResource($item->brand), null),
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}