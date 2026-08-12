<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Express;
use Illuminate\Http\JsonResponse;

class ExpressController extends Controller
{
    /**
     * 获取物流公司列表
     *
     * @return JsonResponse 物流公司列表
     */
    public function index(): JsonResponse
    {
        $list = Express::ofEnabled()
            ->orderBy('sort')
            ->get(['id', 'name']);

        return ApiResponse::success($list);
    }
}
