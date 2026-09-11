<?php

namespace App\Http\Resources\Mall;

use App\Models\Mall\CartItem;
use App\Services\Mall\ProductDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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

        // 批量取身份折扣（防 N+1），金额以实时计算为准
        $percentMap = Auth::user()
            ? service(ProductDiscountService::class)->percentForProducts(
                Auth::user(),
                $items->map(fn (CartItem $item) => $item->product)->filter()->unique('id')->values()
            )
            : [];

        $stores = $items
            ->groupBy(fn (CartItem $item) => $item->product?->tenant_id)
            ->map(function (Collection $storeItems) use ($percentMap) {
                $storeConfigure = $storeItems->first()->product->storeConfigure;

                $itemResources = $storeItems->map(
                    fn (CartItem $item) => new CartItemResource($item, $percentMap[$item->product_id] ?? null)
                );

                return [
                    'store' => StoreConfigureResource::make($storeConfigure),
                    'items' => $itemResources,
                    'total_qty' => $storeItems->sum('qty'),
                    'total_amount' => (float) $itemResources->sum(fn (CartItemResource $resource) => (float) $resource->subTotal()),
                ];
            })
            ->values();

        return [
            'cart_id' => $this->resource->id,
            'stores' => $stores,
            'total_qty' => $this->resource->total_qty,
            'total_amount' => (float) $stores->sum(fn (array $store) => $store['total_amount']),
            'is_expired' => $this->resource->isExpired(),
        ];
    }
}
