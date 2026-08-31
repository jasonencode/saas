<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\ProductCollection;
use App\Http\Resources\Mall\TopicResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * 专题列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 专题列表
     */
    public function index(Request $request): JsonResponse
    {
        $topics = Topic::query()
            ->with('products')
            ->orderBy('sort')
            ->get();

        return ApiResponse::success(TopicResource::collection($topics));
    }

    /**
     * 专题详情
     *
     * @param  string  $type  专题 slug
     *
     * @return JsonResponse 专题详情
     */
    public function show(string $type): JsonResponse
    {
        $topic = Topic::ofSlug($type)
            ->with('products')
            ->first();

        if (!$topic) {
            return ApiResponse::notFound('专题不存在');
        }

        return ApiResponse::success(TopicResource::make($topic));
    }

    /**
     * 专题商品列表
     *
     * @param  Request  $request  请求
     * @param  string  $type  专题 slug
     *
     * @return JsonResponse 商品列表
     */
    public function products(Request $request, string $type): JsonResponse
    {
        $topic = Topic::ofSlug($type)->first();

        if (!$topic) {
            return ApiResponse::notFound('专题不存在');
        }

        $products = $topic->products()
            ->with(['brand', 'category', 'storeConfigure', 'tags'])
            ->withSum('skus', 'sale')
            ->paginate(min($request->integer('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ProductCollection::make($products));
    }
}
