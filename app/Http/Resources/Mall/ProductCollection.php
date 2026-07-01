<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\BaseCollection;
use App\Models\Mall\Product;
use Illuminate\Http\Request;

class ProductCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function (Product $item) {
                return [
                    'goods_id' => $item->id,
                    'name' => $item->name,
                    'cover' => $item->cover_url,
                    'price' => $item->price,
                    'origin_price' => $item->origin_price,
                    'views' => $item->views,
                    'sales' => $item->total_sale,
                    'store' => $this->when($item->storeConfigure, new StoreConfigureResource($item->storeConfigure), null),
                    'brand' => $this->when($item->brand, new BrandResource($item->brand), null),
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}
