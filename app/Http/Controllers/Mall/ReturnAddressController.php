<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\ReturnAddressResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\ReturnAddress;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;

class ReturnAddressController extends Controller
{
    /**
     * 获取退货地址列表
     *
     * 按当前租户过滤；无租户上下文返回空列表，避免泄露其他店铺联系人信息。
     *
     * @return JsonResponse 退货地址列表
     */
    public function index(): JsonResponse
    {
        $tenantId = TenantResolver::current()?->getKey() ?? 0;

        $list = ReturnAddress::query()
            ->where('tenant_id', $tenantId)
            ->ofEnabled()
            ->orderBy('is_default', 'desc')
            ->bySort()
            ->get();

        return ApiResponse::success(ReturnAddressResource::collection($list));
    }
}
