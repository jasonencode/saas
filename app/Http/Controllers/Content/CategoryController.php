<?php

namespace App\Http\Controllers\Content;

use App\Enums\Content\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Content\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\ContentCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * 获取内容分类列表
     *
     * @return JsonResponse 内容分类列表
     */
    public function index(): JsonResponse
    {
        $list = ContentCategory::ofEnabled()->get();

        return ApiResponse::success(CategoryResource::collection($list));
    }

    /**
     * 获取内容分类详情
     *
     * @param  ContentCategory  $category  内容分类
     *
     * @return JsonResponse 内容分类详情
     */
    public function show(ContentCategory $category): JsonResponse
    {
        if ($category->type !== CategoryType::Content || $category->isDisabled()) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(CategoryResource::make($category));
    }
}
