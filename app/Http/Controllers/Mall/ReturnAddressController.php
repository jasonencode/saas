<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\ReturnAddressResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\ReturnAddress;
use Illuminate\Http\JsonResponse;

class ReturnAddressController extends Controller
{
    /**
     * 获取退货地址列表
     *
     * @return JsonResponse 退货地址列表
     */
    public function index(): JsonResponse
    {
        $list = ReturnAddress::ofEnabled()
            ->bySort()
            ->get();

        return ApiResponse::success(ReturnAddressResource::collection($list));
    }
}
