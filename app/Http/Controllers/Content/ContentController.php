<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\IndexContentRequest;
use App\Http\Resources\Content\ContentCollection;
use App\Http\Resources\Content\ContentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Content;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentController extends Controller
{
    /**
     * 获取内容列表
     *
     * 无租户上下文时返回空列表，避免泄露其他租户内容。
     *
     * @param  IndexContentRequest  $request  请求
     *
     * @return JsonResponse 内容列表
     */
    public function index(IndexContentRequest $request): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey() ?? 0;
        $tagId = $request->validated('tag_id');
        $categoryId = $request->validated('category_id');

        $content = Content::query()
            ->where('tenant_id', $tenantId)
            ->ofEnabled()
            ->with('tags')
            ->when($tagId !== null, fn (Builder $builder) => $builder->whereHas('tags', fn ($query) => $query->whereKey($tagId)))
            ->when($categoryId !== null, fn (Builder $builder) => $builder->where('category_id', $categoryId))
            ->bySort()
            ->paginate(min((int) $request->validated('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ContentCollection::make($content));
    }

    /**
     * 获取内容详情
     *
     * @param  Request  $request  请求
     * @param  Content  $content  内容
     *
     * @return JsonResponse 内容详情
     */
    public function show(Request $request, Content $content): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey();

        if (!$tenantId || (int) $content->tenant_id !== (int) $tenantId || $content->isDisabled()) {
            return ApiResponse::notFound();
        }

        // 同一 IP 30 分钟内只计一次浏览，避免循环刷新刷高曝光
        if (Cache::add('content_view:'.$content->getKey().':'.$request->ip(), true, now()->addMinutes(30))) {
            $content->increment('views');
        }

        $content->load('tags');

        return ApiResponse::success(ContentResource::make($content));
    }
}
