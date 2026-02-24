<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Resources\GoodsCollection;
use App\Http\Resources\GoodsResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $name = $request->name;
        $storeId = $request->store_id;
        $brandId = $request->brand_id;

        $goods = Product::ofUp()
            ->when($storeId, function (Builder $builder, $storeId) {
                $builder->where('store_id', $storeId);
            })
            ->when($brandId, function (Builder $builder, $brandId) {
                $builder->where('brand_id', $brandId);
            })
            ->when($name, function (Builder $builder, $name) {
                $builder->where('name', 'like', "%$name%");
            })
            ->latest()
            ->paginate((int) $request->limit);

        return $this->success(new GoodsCollection($goods));
    }

    public function show(Product $goods): JsonResponse
    {
        if ($goods->status !== ProductStatus::Up) {
            return $this->error('商品不存在', 404);
        }

        return $this->success(new GoodsResource($goods));
    }
}
