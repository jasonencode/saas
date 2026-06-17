<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SubscribeIdentityRequest;
use App\Http\Resources\User\IdentityResource;
use App\Http\Responses\ApiResponse;
use App\Models\User\Identity;
use App\Models\User\IdentityOrder;
use App\Services\User\IdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class IdentityController extends Controller
{
    public function __construct(
        private readonly IdentityService $identityService,
    ) {
    }

    /**
     * 当前用户有效身份列表
     */
    public function index(): JsonResponse
    {
        $identities = $this->identityService->activeIdentities(Auth::user());

        return ApiResponse::success(IdentityResource::collection($identities));
    }

    /**
     * 可订阅的身份列表
     */
    public function available(): JsonResponse
    {
        $user = Auth::user();

        $identities = Identity::where('tenant_id', $user->tenant_id)
            ->where('can_subscribe', true)
            ->where('status', true)
            ->orderBy('sort')
            ->get();

        return ApiResponse::success(IdentityResource::collection($identities));
    }

    /**
     * 检查是否持有指定身份
     */
    public function check(Identity $identity): JsonResponse
    {
        $user = Auth::user();

        if ($identity->tenant_id !== $user->tenant_id) {
            return ApiResponse::notFound('身份不存在');
        }

        $has = $this->identityService->has($user, $identity);
        $expiringSoon = false;

        if ($has) {
            $expiring = $this->identityService->expiringSoon($user, 7);
            $expiringSoon = $expiring->contains($identity);
        }

        return ApiResponse::success([
            'has' => $has,
            'expiring_soon' => $expiringSoon,
        ]);
    }

    /**
     * 创建身份订阅订单
     */
    public function subscribe(SubscribeIdentityRequest $request, Identity $identity): JsonResponse
    {
        $user = Auth::user();

        if ($identity->tenant_id !== $user->tenant_id) {
            return ApiResponse::notFound('身份不存在');
        }

        if (!$identity->can_subscribe || !$identity->status) {
            return ApiResponse::error('该身份不可订阅');
        }

        if ($identity->is_unique && $this->identityService->has($user, $identity)) {
            return ApiResponse::error('您已持有该身份，无需重复订阅');
        }

        $qty = $request->integer('qty', 1);

        try {
            $order = $this->identityService->purchase($user, $identity, (float) $identity->price, $qty);

            return ApiResponse::created([
                'order_id' => $order->id,
                'order_no' => $order->no,
                'amount' => $order->amount,
                'status' => $order->status->value,
                'status_label' => $order->status->getLabel(),
            ], '订阅订单创建成功');
        } catch (Throwable $e) {
            return ApiResponse::error('创建订单失败: '.$e->getMessage());
        }
    }

    /**
     * 支付身份订阅订单
     */
    public function pay(IdentityOrder $order): JsonResponse
    {
        $user = Auth::user();

        if ($order->user_id !== $user->getKey() || $order->tenant_id !== $user->tenant_id) {
            return ApiResponse::notFound('订单不存在');
        }

        try {
            $this->identityService->payOrder($order);

            return ApiResponse::success([
                'order_id' => $order->id,
                'order_no' => $order->no,
                'status' => $order->fresh()->status->value,
            ], '支付成功，身份已授予');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error('支付失败: '.$e->getMessage());
        }
    }

    /**
     * 用户身份订单列表
     */
    public function orders(): JsonResponse
    {
        $user = Auth::user();

        $orders = IdentityOrder::where('user_id', $user->getKey())
            ->where('tenant_id', $user->tenant_id)
            ->with('identity')
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success($orders);
    }
}
