<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\StoreConfigureCollection;
use App\Http\Resources\Mall\StoreConfigureResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\StoreConfigure;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * 店铺列表（分页）
     */
    public function index(): JsonResponse
    {
        $stores = StoreConfigure::query()
            ->where('enabled', true)
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(StoreConfigureCollection::make($stores));
    }

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
