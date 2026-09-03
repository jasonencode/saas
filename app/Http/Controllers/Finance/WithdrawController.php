<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\Finance\StoreWithdrawOrderRequest;
use App\Http\Resources\Finance\WithdrawOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\UserAccount;
use App\Models\Finance\WithdrawOrder;
use App\Services\Finance\UserAccountService;
use App\Services\Finance\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class WithdrawController
{
    use AuthorizesModelAccess;

    /**
     * 提现订单列表
     *
     * @return JsonResponse 提现订单列表
     */
    public function index(): JsonResponse
    {
        $orders = WithdrawOrder::where('user_id', Auth::id())
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(WithdrawOrderResource::collection($orders));
    }

    /**
     * 创建提现订单
     *
     * @param  StoreWithdrawOrderRequest  $request  提现请求
     *
     * @return JsonResponse 创建的提现订单
     */
    public function store(StoreWithdrawOrderRequest $request): JsonResponse
    {
        try {
            $account = UserAccount::find(Auth::id());

            if (!$account) {
                return ApiResponse::error('用户账户不存在');
            }

            $accountService = service(UserAccountService::class);

            if (!$accountService->verifyPaymentPassword($account, $request->validated('payment_password'))) {
                return ApiResponse::error('支付密码错误');
            }

            $order = service(WithdrawService::class)->create(
                userId: Auth::id(),
                tenantId: Auth::user()?->tenant_id,
                amount: $request->validated('amount'),
                gateway: $request->validated('gateway'),
                accountInfo: $request->validated('account_info'),
                remark: $request->validated('remark'),
            );

            return ApiResponse::created(WithdrawOrderResource::make($order));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 查询提现订单状态
     *
     * @param  WithdrawOrder  $order  提现订单
     *
     * @return JsonResponse 提现订单详情
     */
    public function show(WithdrawOrder $order): JsonResponse
    {
        $this->checkPermission($order);

        return ApiResponse::success(WithdrawOrderResource::make($order));
    }

    /**
     * 取消提现订单
     *
     * @param  WithdrawOrder  $order  提现订单
     *
     * @return JsonResponse 操作结果
     */
    public function cancel(WithdrawOrder $order): JsonResponse
    {
        $this->checkPermission($order);

        if ($order->user_id !== Auth::id()) {
            return ApiResponse::error('无权操作此订单');
        }

        try {
            service(WithdrawService::class)->cancel($order);

            return ApiResponse::success(WithdrawOrderResource::make($order->fresh()));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 获取账户可提现余额
     *
     * @return JsonResponse 可提现余额
     */
    public function balance(): JsonResponse
    {
        $account = UserAccount::find(Auth::id());

        return ApiResponse::success([
            'balance' => $account?->balance ?? 0,
            'frozen_balance' => $account?->frozen_balance ?? 0,
        ]);
    }
}
