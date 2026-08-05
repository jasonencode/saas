<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Content\SinglePageCollection;
use App\Http\Resources\Content\SinglePageResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\SinglePage;
use Illuminate\Http\JsonResponse;

class SinglePageController extends Controller
{
    /**
     * 获取单页内容列表
     *
     * @return JsonResponse 单页内容列表
     */
    public function index(): JsonResponse
    {
        $singlePages = SinglePage::ofEnabled()
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
        $singlePage = SinglePage::where('slug', $slug)
            ->ofEnabled()
            ->firstOrFail();

        return ApiResponse::success(SinglePageResource::make($singlePage));
    }
}
