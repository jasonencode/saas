<?php

namespace App\Http\Resources\Mall;

use App\Models\Mall\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CartResource extends JsonResource
{
    /**
     * 转换为数组格式
     *
     * 跨店购物车：商品项按店铺（租户）分组返回，便于前端按店铺展示
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', $this->resource->items, collect());

        $stores = $items
            ->groupBy(fn (CartItem $item) => $item->product?->tenant_id)
            ->map(function (Collection $storeItems) {
                $storeConfigure = $storeItems->first()->product?->storeConfigure;

                return [
                    'store' => $storeConfigure
                        ? new StoreConfigureResource($storeConfigure)
                        : [
                            'tenant_id' => $storeItems->first()->product?->tenant_id,
                            'store_name' => null,
                        ],
                    'items' => CartItemResource::collection($storeItems),
                    'total_qty' => $storeItems->sum('qty'),
                    'total_amount' => (float) $storeItems->sum(fn (CartItem $item) => $item->qty * (float) $item->price_at_add),
                ];
            })
            ->values();

        return [
            'cart_id' => $this->resource->id,
            'stores' => $stores,
            'total_qty' => $this->resource->total_qty,
            'total_amount' => $this->resource->total_amount,
            'is_expired' => $this->resource->isExpired(),
        ];
    }
}
