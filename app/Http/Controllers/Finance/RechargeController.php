<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderType;
use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\Finance\StoreRechargeOrderRequest;
use App\Http\Resources\Finance\RechargeOrderCollection;
use App\Http\Resources\Finance\RechargeOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\RechargeOrder;
use App\Services\Finance\RechargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RechargeController
{
    use AuthorizesModelAccess;

    /**
     * 充值订单列表
     *
     * @return JsonResponse 充值订单列表
     */
    public function index(): JsonResponse
    {
        $orders = RechargeOrder::where('user_id', Auth::id())
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(RechargeOrderCollection::make($orders));
    }

    /**
     * 创建充值订单
     *
     * @param  StoreRechargeOrderRequest  $request  充值请求
     *
     * @return JsonResponse 创建的充值订单
     */
    public function store(StoreRechargeOrderRequest $request): JsonResponse
    {
        try {
            $order = service(RechargeService::class)->create(
                userId: Auth::id(),
                tenantId: Auth::user()?->tenant_id,
                amount: $request->validated('amount'),
                type: RechargeOrderType::from($request->validated('type')),
                gateway: PaymentGateway::from($request->validated('gateway')),
                remark: $request->validated('remark'),
            );

            return ApiResponse::created(RechargeOrderResource::make($order));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 查询充值订单状态
     *
     * @param  RechargeOrder  $order  充值订单
     *
     * @return JsonResponse 充值订单详情
     */
    public function show(RechargeOrder $order): JsonResponse
    {
        $this->checkPermission($order);

        return ApiResponse::success(RechargeOrderResource::make($order));
    }
}
