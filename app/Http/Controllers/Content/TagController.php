<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\TagResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\ContentTag;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * 获取内容标签列表
     *
     * 无租户上下文时返回空列表，标签计数也只统计当前租户内容。
     *
     * @return JsonResponse 内容标签列表
     */
    public function index(): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey() ?? 0;

        $tags = ContentTag::query()
            ->where('tenant_id', $tenantId)
            ->withCount(['contents' => fn ($query) => $query->where('tenant_id', $tenantId)])
            ->get();

        return ApiResponse::success(TagResource::collection($tags));
    }
}
