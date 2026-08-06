<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\TagResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\ProductTag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * 获取商品标签列表
     *
     * @return JsonResponse 商品标签列表
     */
    public function index(): JsonResponse
    {
        $tags = ProductTag::query()
            ->withCount('products')
            ->get();

        return ApiResponse::success(TagResource::collection($tags));
    }
}
