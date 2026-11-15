<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\SinglePageCollection;
use App\Http\Resources\Content\SinglePageResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\SinglePage;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;

class SinglePageController extends Controller
{
    /**
     * 获取单页内容列表
     *
     * 无租户上下文时返回空列表，避免返回其他租户页面。
     *
     * @return JsonResponse 单页内容列表
     */
    public function index(): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey() ?? 0;

        $singlePages = SinglePage::query()
            ->where('tenant_id', $tenantId)
            ->ofEnabled()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(SinglePageCollection::make($singlePages));
    }

    /**
     * 获取单页内容详情
     *
     * @param  string  $slug  别名
     *
     * @return JsonResponse 单页内容详情
     */
    public function show(string $slug): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey();

        if (!$tenantId) {
            return ApiResponse::notFound();
        }

        $singlePage = SinglePage::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->ofEnabled()
            ->firstOrFail();

        return ApiResponse::success(SinglePageResource::make($singlePage));
    }
}
