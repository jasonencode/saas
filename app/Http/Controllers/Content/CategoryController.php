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
            ->get()
            ->map(static fn (ContentCategory $category): array => [
                'category_id' => $category->getKey(),
                'parent_id' => $category->parent_id ?? 0,
                'level' => $category->level,
                'name' => $category->name,
                'description' => $category->description,
                'cover' => $category->cover_url,
            ])
            ->all();

        // 一次查询后在内存建树，避免逐节点查询子分类（CONT-07）
        return ApiResponse::success(list2tree($list, 'category_id', 'parent_id', 'children', 0));
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
