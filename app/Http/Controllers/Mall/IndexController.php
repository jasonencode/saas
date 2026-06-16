<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\BannerResource;
use App\Http\Resources\Mall\BrandResource;
use App\Http\Resources\Mall\ProductCollection;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Banner;
use App\Models\Mall\Brand;
use App\Models\Mall\Product;
use Illuminate\Http\JsonResponse;

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

        $brands = Brand::ofEnabled()
            ->bySort()
            ->limit(20)
            ->get();

        $products = Product::ofUp()
            ->with(['brand', 'category', 'storeConfigure'])
            ->withSum('skus', 'sale')
            ->latest()
            ->limit(10)
            ->get();

        return ApiResponse::success([
            'banners' => BannerResource::collection($banners),
            'brands' => BrandResource::collection($brands),
            'products' => ProductCollection::make($products),
        ]);
    }

    /**
     * 店铺品牌列表
     */
    public function brands(): JsonResponse
    {
        $list = Brand::ofEnabled()
            ->bySort()
            ->get();

        return ApiResponse::success(BrandResource::collection($list));
    }

    /**
     * 轮播图列表
     */
    public function banners(): JsonResponse
    {
        $list = Banner::ofEnabled()
            ->bySort()
            ->get();

        return ApiResponse::success(BannerResource::collection($list));
    }
}
