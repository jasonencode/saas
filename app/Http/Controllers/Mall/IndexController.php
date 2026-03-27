<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    /**
     * 商城首页
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success();
    }

    /**
     * 店铺品牌列表
     */
    public function brands(): JsonResponse
    {
        return ApiResponse::success();
    }

    /**
     * 轮播图列表
     */
    public function banners(): JsonResponse
    {
        return ApiResponse::success();
    }
}
