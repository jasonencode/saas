<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\ContentCollection;
use App\Http\Resources\Content\ContentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Content;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    /**
     * 获取内容列表
     *
     * @return JsonResponse 内容列表
     */
    public function index(): JsonResponse
    {
        $content = Content::ofEnabled()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ContentCollection::make($content));
    }

    /**
     * 获取内容详情
     *
     * @param  Content  $content  内容
     *
     * @return JsonResponse 内容详情
     */
    public function show(Content $content): JsonResponse
    {
        if ($content->isDisabled()) {
            return ApiResponse::notFound();
        }

        $content->increment('views');

        return ApiResponse::success(ContentResource::make($content));
    }
}
