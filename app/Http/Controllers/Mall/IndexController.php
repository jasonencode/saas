<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\CategoryResource;
use App\Http\Resources\Mall\BannerResource;
use App\Http\Resources\Mall\BrandResource;
use App\Http\Resources\Mall\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Banner;
use App\Models\Mall\Brand;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * 商城首页
     */
    public function index(): JsonResponse
    {
        $banners = Banner::ofEnabled()
            ->bySort()
            ->limit(10)
            ->get();

        $categories = ProductCategory::ofEnabled()
            ->where('is_home', true)
            ->bySort()
            ->limit(5)
            ->get();

        $products = Product::ofUp()
            ->bySort()
            ->with(['brand', 'category', 'storeConfigure'])
            ->withSum('skus', 'sale')
            ->limit(20)
            ->get();

        return ApiResponse::success([
            'banners' => BannerResource::collection($banners),
            'categories' => CategoryResource::collection($categories),
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * 店铺品牌列表
     */
    public function brands(Request $request): JsonResponse
    {
        $list = Brand::ofEnabled()
            ->when($request->input('tenant_id'), function (Builder $builder, int $tenantId) {
                $builder->where('tenant_id', $tenantId);
            })
            ->bySort()
            ->get();

        return ApiResponse::success(BrandResource::collection($list));
    }

    /**
     * 轮播图列表
     */
    public function banners(Request $request): JsonResponse
    {
        $list = Banner::ofEnabled()
            ->when($request->input('tenant_id'), function (Builder $builder, int $tenantId) {
                $builder->where('tenant_id', $tenantId);
            })
            ->bySort()
            ->get();

        return ApiResponse::success(BannerResource::collection($list));
    }
}
