<?php

namespace App\Http\Controllers\Mall;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\ProductCollection;
use App\Http\Resources\Mall\ProductResource;
use App\Http\Responses\ApiResponse;
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

        $products = Product::ofUp()
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

        return ApiResponse::success(ProductCollection::make($products));
    }

    public function show(Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        return ApiResponse::success(ProductResource::make($product));
    }
}
