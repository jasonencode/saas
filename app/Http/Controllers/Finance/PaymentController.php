<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Foundation\SocialiteProvider;
use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\Finance\RefundRequest;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Http\Resources\Finance\PaymentOrderResource;
use App\Http\Resources\Finance\PaymentRefundResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\PaymentOrder;
use App\Models\Foundation\Socialite;
use App\Models\Foundation\WechatPayment;
use App\Services\Foundation\WechatPaymentService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController
{
    use AuthorizesModelAccess;

    /**
     * 发起支付
     *
     * @param  StorePaymentRequest  $request  支付请求
     *
     * @return JsonResponse 创建的支付单
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        /** @var PaymentOrder $payment */
        $payment = PaymentOrder::create([
            'user_id' => Auth::id(),
            'tenant_id' => TenantResolver::current()?->getKey(),
            'amount' => $request->validated('amount'),
            'gateway' => $request->validated('gateway'),
            'paymentable_type' => $request->validated('paymentable_type'),
            'paymentable_id' => $request->validated('paymentable_id'),
            'expired_at' => now()->addMinutes(30),
        ]);

        return ApiResponse::created(PaymentOrderResource::make($payment));
    }

    /**
     * 查询支付状态
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 支付单详情
     */
    public function show(PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        return ApiResponse::success(PaymentOrderResource::make($payment));
    }

    /**
     * 申请退款
     *
     * @param  RefundRequest  $request  退款请求
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 创建的退款单
     */
    public function refund(RefundRequest $request, PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        if ($payment->status !== PaymentStatus::Paid) {
            return ApiResponse::error('该订单未支付，无法申请退款');
        }

        // 计算已退款金额（待审核 + 已批准 + 处理中 + 已完成）
        $refundedAmount = $payment->refunds()
            ->whereIn('status', [
                PaymentRefundStatus::Pending,
                PaymentRefundStatus::Approved,
                PaymentRefundStatus::Processing,
                PaymentRefundStatus::Completed,
            ])
            ->sum('amount');

        $refundableAmount = bcsub($payment->amount, $refundedAmount, 2);

        if (bccomp($refundableAmount, '0.01', 2) < 0) {
            return ApiResponse::error('该订单可退款金额不足');
        }

        $refund = $payment->refunds()->create([
            'tenant_id' => $payment->tenant_id,
            'amount' => $request->validated('amount'),
            'reason' => $request->validated('reason'),
            'status' => PaymentRefundStatus::Pending,
            'created_by_type' => Auth::user()?->getMorphClass(),
            'created_by_id' => Auth::id(),
        ]);

        return ApiResponse::created(new PaymentRefundResource($refund));
    }

    /**
     * 发起支付（获取支付参数）
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 支付参数
     */
    public function pay(PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        if ($payment->status !== PaymentStatus::Pending) {
            return ApiResponse::error('该订单状态不可支付');
        }

        if ($payment->expired_at->isPast()) {
            return ApiResponse::error('该订单已过期');
        }

        if ($payment->gateway !== PaymentGateway::Wechat) {
            return ApiResponse::error('暂不支持该支付方式');
        }

        $wechatPayment = WechatPayment::ofTenant(TenantResolver::current()?->getKey())->first();

        if (!$wechatPayment) {
            return ApiResponse::error('未配置微信支付');
        }

        $socialite = Socialite::where('user_id', Auth::id())
            ->where('provider', SocialiteProvider::WeChat)
            ->first();

        if (!$socialite) {
            return ApiResponse::error('请先绑定微信账号');
        }

        $service = service(WechatPaymentService::class);

        $result = $service->makeOrder(
            payment: $wechatPayment,
            openid: $socialite->provider_id,
            orderNo: $payment->no,
            amount: (int) bcmul($payment->amount, '100', 0),
            description: $payment->paymentable?->title ?? '订单支付',
            notifyUrl: route('payments.notify', $payment->id),
        );

        return ApiResponse::success([
            'appId' => $wechatPayment->wechat?->app_id ?? '',
            'timeStamp' => $result['time_stamp'],
            'nonceStr' => $result['nonce_str'],
            'package' => $result['package'],
            'signType' => $result['sign_type'],
            'paySign' => $result['pay_sign'],
        ]);
    }

    /**
     * 微信支付回调
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 处理结果
     */
    public function notify(PaymentOrder $payment): JsonResponse
    {
        $wechatPayment = WechatPayment::ofTenant($payment->tenant_id)->first();

        if (!$wechatPayment) {
            return response()->json(['code' => 'FAIL', 'message' => '未配置微信支付']);
        }

        $service = service(WechatPaymentService::class);

        try {
            $data = $service->handleNotify($wechatPayment);

            if ($data['out_trade_no'] !== $payment->no) {
                return response()->json(['code' => 'FAIL', 'message' => '订单号不匹配']);
            }

            if ($data['trade_state'] === 'SUCCESS') {
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ]);

                // 触发订单支付成功事件
                // event(new OrderPaid($payment->paymentable, Auth::user()));
            }

            return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
        } catch (\Throwable $e) {
            return response()->json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }
    }
}
