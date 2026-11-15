<?php

namespace App\Http\Controllers\Content;

use App\Enums\Content\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Content\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\ContentCategory;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * 获取内容分类列表
     *
     * 无租户上下文时返回空树，避免返回其他租户分类。
     *
     * @return JsonResponse 内容分类列表
     */
    public function index(): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey() ?? 0;

        $list = ContentCategory::query()
            ->where('tenant_id', $tenantId)
            ->ofEnabled()
            ->bySort()
            ->get();

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
        $tenantId = TenantResolver::current()?->getKey();

        if (!$tenantId
            || (int) $category->tenant_id !== (int) $tenantId
            || $category->type !== CategoryType::Content
            || $category->isDisabled()) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(CategoryResource::make($category));
    }
}
