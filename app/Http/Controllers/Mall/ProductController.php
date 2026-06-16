<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\ProductCollection;
use App\Http\Resources\Mall\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::ofUp()
            ->with(['brand', 'category', 'storeConfigure'])
            ->withSum('skus', 'sale')
            ->when($request->tenant(), function (Builder $builder, $tenant) {
                $builder->where('tenant_id', $tenant->getKey());
            })
            ->when($request->filled('name'), function (Builder $builder, string $name) {
                $builder->where('name', 'like', "%{$name}%");
            })
            ->when($request->filled('category_id'), function (Builder $builder, int $categoryId) {
                $builder->where('category_id', $categoryId);
            })
            ->when($request->filled('brand_id'), function (Builder $builder, int $brandId) {
                $builder->where('brand_id', $brandId);
            })
            ->when($request->filled('min_price'), function (Builder $builder, string $minPrice) {
                $builder->whereHas('skus', fn ($q) => $q->where('price', '>=', $minPrice));
            })
            ->when($request->filled('max_price'), function (Builder $builder, string $maxPrice) {
                $builder->whereHas('skus', fn ($q) => $q->where('price', '<=', $maxPrice));
            })
            ->when($request->filled('sort'), function (Builder $builder, string $sort) {
                $builder->orderByMatch($sort);
            }, function (Builder $builder) {
                $builder->latest();
            })
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ProductCollection::make($products));
    }

    public function show(Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        $product->load(['skus', 'brand', 'category', 'storeConfigure']);

        return ApiResponse::success(ProductResource::make($product));
    }
}
