<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\TagResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\ContentTag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * 获取内容标签列表
     *
     * @return JsonResponse 内容标签列表
     */
    public function index(): JsonResponse
    {
        $tags = ContentTag::query()
            ->withCount('contents')
            ->get();

        return ApiResponse::success(TagResource::collection($tags));
    }
}
