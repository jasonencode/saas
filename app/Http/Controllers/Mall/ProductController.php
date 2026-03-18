<?php

namespace App\Http\Controllers\Mall;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\GoodsCollection;
use App\Http\Resources\Mall\GoodsResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->tenant();
        $name = $request->name;
        $brandId = $request->brand_id;

        $goods = Product::ofUp()
            ->when($tenantId, function (Builder $builder, $storeId) {
                $builder->where('tenant_id', $storeId);
            })
            ->when($brandId, function (Builder $builder, $brandId) {
                $builder->where('brand_id', $brandId);
            })
            ->when($name, function (Builder $builder, $name) {
                $builder->where('name', 'like', "%$name%");
            })
            ->latest()
            ->paginate((int) $request->limit);

        return $this->success(GoodsCollection::make($goods));
    }

    public function show(Product $goods): JsonResponse
    {
        if ($goods->status !== ProductStatus::Up) {
            return $this->error('商品不存在', 404);
        }

        return $this->success(GoodsResource::make($goods));
    }
}
