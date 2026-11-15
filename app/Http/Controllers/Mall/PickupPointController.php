<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\PickupPointResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\PickupPoint;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;

class PickupPointController extends Controller
{
    /**
     * 获取自提点列表
     *
     * 按当前租户过滤；无租户上下文返回空列表，避免泄露其他店铺地址与联系人。
     *
     * @return JsonResponse 自提点列表
     */
    public function index(): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey() ?? 0;

        $list = PickupPoint::query()
            ->where('tenant_id', $tenantId)
            ->ofEnabled()
            ->bySort()
            ->get();

        return ApiResponse::success(PickupPointResource::collection($list));
    }
}
