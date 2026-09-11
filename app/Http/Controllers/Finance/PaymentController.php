<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Foundation\SocialiteProvider;
use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\Finance\PayPaymentRequest;
use App\Http\Requests\Finance\RefundRequest;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Http\Resources\Finance\PaymentOrderResource;
use App\Http\Resources\Finance\PaymentRefundResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\PaymentOrder;
use App\Models\Foundation\Socialite;
use App\Models\Foundation\WechatPayment;
use App\Services\Finance\PaymentableResolver;
use App\Services\Finance\PaymentRefundService;
use App\Services\Finance\PaymentService;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

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
        $paymentable = null;

        if ($request->filled('paymentable_type') && $request->filled('paymentable_id')) {
            // paymentable_type 已通过 Rule::in 白名单校验（简短标识），由 Resolver 解析为模型并取 tenant_id
            $paymentable = PaymentableResolver::resolve(
                $request->validated('paymentable_type'),
                (int) $request->validated('paymentable_id'),
            );

            if (!$paymentable) {
                return ApiResponse::error('支付关联业务不存在');
            }
        }

        $data = [
            'user_id' => Auth::id(),
            'tenant_id' => $paymentable?->tenant_id,
            'amount' => $paymentable
                ? PaymentableResolver::amountOf($paymentable)
                : $request->validated('amount'),
            'gateway' => $request->validated('gateway'),
            'expired_at' => now()->addMinutes(30),
        ];

        if ($paymentable) {
            // 走 setPaymentableAttribute 修改器，存储完整 morph 类名与 ID
            $data['paymentable'] = $paymentable;
        }

        /** @var PaymentOrder $payment */
        $payment = PaymentOrder::create($data);

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
     * 创建支付退款单（Pending），需财务审核通过后才原路退回。
     *
     * @param  RefundRequest  $request  退款请求
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 创建的退款单
     */
    public function refund(RefundRequest $request, PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        $creator = Auth::user();

        try {
            $refund = service(PaymentRefundService::class)->create(
                payment: $payment,
                amount: (float) $request->validated('amount'),
                reason: $request->validated('reason'),
                creator: $creator instanceof Model ? $creator : null,
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::created(PaymentRefundResource::make($refund));
    }

    /**
     * 发起支付（获取支付参数）
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 支付参数
     */
    public function pay(PayPaymentRequest $request, PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        if ($payment->status !== PaymentStatus::Pending) {
            return ApiResponse::error('该订单状态不可支付');
        }

        if ($payment->expired_at->isPast()) {
            return ApiResponse::error('该订单已过期');
        }

        // 单入口按网关分流，各网关支付逻辑独立成方法
        return match ($payment->gateway) {
            PaymentGateway::Balance => $this->payBalance($request, $payment),
            PaymentGateway::Wechat => $this->payWechat($payment),
            default => ApiResponse::error('暂不支持该支付方式'),
        };
    }

    /**
     * 余额支付
     *
     * @param  PayPaymentRequest  $request  支付请求
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 支付后的支付单
     */
    private function payBalance(PayPaymentRequest $request, PaymentOrder $payment): JsonResponse
    {
        $password = $request->safe()->string('payment_password');

        if (blank($password)) {
            return ApiResponse::error('请输入支付密码');
        }

        try {
            service(PaymentService::class)->payByBalance($payment, $password, Auth::user());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(PaymentOrderResource::make($payment->fresh()));
    }

    /**
     * 微信支付（获取支付参数）
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return JsonResponse 支付参数
     */
    private function payWechat(PaymentOrder $payment): JsonResponse
    {
        $wechatPayment = WechatPayment::ofTenant($payment->tenant_id)->first();

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
                // 标记支付单已支付并推进关联业务（商城订单 / 充值单），与余额支付共用同一出口
                DB::transaction(function () use ($payment, $data): void {
                    service(PaymentService::class)->markPaidWithBusiness(
                        payment: $payment,
                        paymentNo: $data['transaction_id'] ?? null,
                    );
                });
            }

            return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }
    }
}
