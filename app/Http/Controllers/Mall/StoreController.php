<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\StoreConfigureResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\StoreConfigure;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * 获取店铺信息
     */
    public function show(int $tenantId): JsonResponse
    {
        $store = StoreConfigure::ofTenant($tenantId)->first();

        if (!$store) {
            return ApiResponse::error('店铺未开通');
        }

        return ApiResponse::success(StoreConfigureResource::make($store));
    }
}
