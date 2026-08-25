<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\PickupPointResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\PickupPoint;
use Illuminate\Http\JsonResponse;

class PickupPointController extends Controller
{
    /**
     * 获取自提点列表
     *
     * @return JsonResponse 自提点列表
     */
    public function index(): JsonResponse
    {
        $list = PickupPoint::ofEnabled()
            ->bySort()
            ->get();

        return ApiResponse::success(PickupPointResource::collection($list));
    }
}
