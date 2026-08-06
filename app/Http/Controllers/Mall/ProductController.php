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
    /**
     * 获取商品列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 商品列表
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::ofUp()
            ->with(['brand', 'category', 'storeConfigure', 'tags'])
            ->withSum('skus', 'sale')
            ->when($request->tenant(), function (Builder $builder, $tenant) {
                $builder->where('tenant_id', $tenant->getKey());
            })
            ->when($request->filled('name'), function (Builder $builder, string $name) {
                $builder->search('name', $name);
            })
            ->when($request->filled('category_id'), function (Builder $builder, int $categoryId) {
                $builder->where('category_id', $categoryId);
            })
            ->when($request->filled('brand_id'), function (Builder $builder, int $brandId) {
                $builder->where('brand_id', $brandId);
            })
            ->when($request->filled('tag_id'), function (Builder $builder, int $tagId) {
                $builder->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
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

    /**
     * 获取商品详情
     *
     * @param  Product  $product  商品
     *
     * @return JsonResponse 商品详情
     */
    public function show(Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        $product->load(['skus', 'brand', 'category', 'storeConfigure', 'tags']);

        return ApiResponse::success(ProductResource::make($product));
    }
}
