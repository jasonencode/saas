<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    /**
     * 商城首页
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->success();
    }

    /**
     * 店铺品牌列表
     *
     * @return JsonResponse
     */
    public function brands(): JsonResponse
    {
        return $this->success();
    }

    /**
     * 轮播图列表
     *
     * @return JsonResponse
     */
    public function banners(): JsonResponse
    {
        return $this->success();
    }
}