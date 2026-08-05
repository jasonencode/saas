<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\ProductCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * 获取商品分类列表
     *
     * @return JsonResponse 商品分类列表
     */
    public function index(): JsonResponse
    {
        $list = ProductCategory::ofEnabled()->get();

        return ApiResponse::success(CategoryResource::collection($list));
    }

    /**
     * 获取商品分类详情
     *
     * @param  ProductCategory  $category  商品分类
     *
     * @return JsonResponse 商品分类详情
     */
    public function show(ProductCategory $category): JsonResponse
    {
        if ($category->isDisabled()) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(CategoryResource::make($category));
    }
}
