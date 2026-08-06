<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\ContentCollection;
use App\Http\Resources\Content\ContentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Content;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * 获取内容列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 内容列表
     */
    public function index(Request $request): JsonResponse
    {
        $content = Content::ofEnabled()
            ->with('tags')
            ->when($request->filled('tag_id'), function (Builder $builder, int $tagId) {
                $builder->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
            })
            ->paginate(min($request->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

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
        $content->load('tags');

        return ApiResponse::success(ContentResource::make($content));
    }
}
