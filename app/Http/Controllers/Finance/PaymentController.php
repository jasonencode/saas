<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Foundation\SocialiteProvider;
use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\Finance\PayPaymentRequest;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Http\Resources\Finance\PaymentOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Foundation\Socialite;
use App\Services\Finance\PaymentableResolver;
use App\Services\Finance\PaymentRefundService;
use App\Services\Finance\PaymentService;
use App\Services\Finance\WechatPaymentResolver;
use App\Services\Foundation\WechatPaymentService;
use App\Services\System\EventLogService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
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
        $user = Auth::user();
        $tenant = TenantResolver::current();

        if ($tenant && !$user->tenants()->whereKey($tenant->getKey())->exists()) {
            return ApiResponse::error('无权操作该租户');
        }

        $type = $request->validated('paymentable_type');
        $id = (int) $request->validated('paymentable_id');

        // paymentable_type 已通过 Rule::in 白名单校验（简短标识），按用户与租户收窄解析
        $paymentable = PaymentableResolver::resolve(
            $type,
            $id,
            $user->getKey(),
            $tenant?->getKey(),
        );

        if (!$paymentable) {
            return ApiResponse::notFound('支付关联业务不存在');
        }

        $tenantId = $tenant?->getKey() ?? $paymentable->tenant_id;
        $gateway = PaymentGateway::from($request->validated('gateway'));

        if ($gateway === PaymentGateway::Wechat && !$tenantId) {
            return ApiResponse::error('缺少租户上下文，无法发起微信支付');
        }

        $wechatPayment = $gateway === PaymentGateway::Wechat
            ? WechatPaymentResolver::forTenant($tenantId)
            : null;

        if ($gateway === PaymentGateway::Wechat && !$wechatPayment) {
            return ApiResponse::error('未配置微信支付');
        }

        try {
            $amount = service(PaymentService::class)->payableAmountFor($paymentable);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return ApiResponse::error($e->getMessage());
        }

        // 清理同一业务单据下已过期的待支付单，避免旧 prepay 继续可用
        PaymentOrder::query()
            ->where('user_id', $user->getKey())
            ->where('paymentable_type', $paymentable->getMorphClass())
            ->where('paymentable_id', $paymentable->getKey())
            ->where('status', PaymentStatus::Pending)
            ->where('expired_at', '<=', now())
            ->update(['status' => PaymentStatus::Canceled]);

        // 切换支付方式时作废其他网关的待支付单，避免同一业务单据存在多张可支付单
        PaymentOrder::query()
            ->where('user_id', $user->getKey())
            ->where('paymentable_type', $paymentable->getMorphClass())
            ->where('paymentable_id', $paymentable->getKey())
            ->where('status', PaymentStatus::Pending)
            ->where('gateway', '!=', $gateway->value)
            ->update(['status' => PaymentStatus::Canceled]);

        // 同一业务单据复用未过期的待支付单，避免重复创建导致重复收款
        $existing = PaymentOrder::query()
            ->where('user_id', $user->getKey())
            ->where('paymentable_type', $paymentable->getMorphClass())
            ->where('paymentable_id', $paymentable->getKey())
            ->where('gateway', $gateway)
            ->where('status', PaymentStatus::Pending)
            ->where('expired_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            return ApiResponse::success(PaymentOrderResource::make($existing));
        }

        $data = [
            'user_id' => $user->getKey(),
            'tenant_id' => $tenantId,
            'amount' => $amount,
            'gateway' => $gateway,
            'wechat_payment_id' => $wechatPayment?->getKey(),
            'expired_at' => now()->addMinutes(30),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // 走 setPaymentableAttribute 修改器，存储完整 morph 类名与 ID
        $data['paymentable'] = $paymentable;

        /** @var PaymentOrder $payment */
        $payment = PaymentOrder::create($data);

        service(EventLogService::class)->write('payment.created', '创建支付单', $user, $payment, null, [
            'gateway' => $payment->gateway->value,
            'amount' => $payment->amount,
            'paymentable_type' => $payment->paymentable_type,
            'paymentable_id' => $payment->paymentable_id,
            'wechat_payment_id' => $payment->wechat_payment_id,
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
        $this->checkPermission($payment, hideExistence: true);

        return ApiResponse::success(PaymentOrderResource::make($payment));
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
        $this->checkPermission($payment, hideExistence: true);

        if ($payment->status !== PaymentStatus::Pending) {
            return ApiResponse::error('该订单状态不可支付');
        }

        if ($payment->expired_at?->isPast()) {
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
        } catch (InvalidArgumentException|RuntimeException $e) {
            return ApiResponse::error($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error('支付失败，请稍后重试');
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
        try {
            $wechatPayment = WechatPaymentResolver::forPayment($payment);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage());
        }

        if (!$payment->wechat_payment_id) {
            // 锁定商户配置，回调/退款/对账统一使用该配置
            $payment->update(['wechat_payment_id' => $wechatPayment->getKey()]);
        }

        $paymentable = $payment->paymentable;

        if (!$paymentable) {
            return ApiResponse::error('支付关联业务已删除');
        }

        try {
            $payableAmount = service(PaymentService::class)->payableAmountFor($paymentable);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return ApiResponse::error($e->getMessage());
        }

        // 支付单金额是建单时的快照：业务金额变更后必须拒绝，否则微信按旧金额收款
        if (bccomp((string) $payment->amount, $payableAmount, 2) !== 0) {
            return ApiResponse::error('订单金额已变更，请刷新后重试');
        }

        $socialite = Socialite::where('user_id', Auth::id())
            ->where('provider', SocialiteProvider::WeChat)
            ->first();

        if (!$socialite) {
            return ApiResponse::error('请先绑定微信账号');
        }

        try {
            $result = service(WechatPaymentService::class)->makeOrder(
                payment: $wechatPayment,
                openid: $socialite->provider_id,
                orderNo: $payment->no,
                amount: (int) bcmul($payableAmount, '100', 0),
                description: $paymentable->title ?? '订单支付',
                notifyUrl: route('payments.notify', $payment->id),
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error('微信支付下单失败，请稍后重试');
        }

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
        if ($payment->gateway !== PaymentGateway::Wechat) {
            return response()->json(['code' => 'FAIL', 'message' => '支付单不支持微信回调']);
        }

        try {
            $wechatPayment = WechatPaymentResolver::forPayment($payment, requireEnabled: false);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }

        try {
            $data = service(WechatPaymentService::class)->handleNotify($wechatPayment, requireEnabled: false);

            // 验签通过后再落审计：渠道可能重复投递，按支付单 + 渠道单号可检索
            service(EventLogService::class)->write('payment.notify_received', '收到支付回调', null, $payment, null, [
                'out_trade_no' => $data['out_trade_no'] ?? null,
                'trade_state' => $data['trade_state'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'wechat_payment_id' => $wechatPayment->getKey(),
            ]);

            if (($data['out_trade_no'] ?? null) !== $payment->no) {
                return $this->failNotify($payment, '回调单号与支付单不匹配', [
                    'out_trade_no' => $data['out_trade_no'] ?? null,
                ]);
            }

            if (($data['trade_state'] ?? null) !== 'SUCCESS') {
                return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
            }

            $amount = (array) ($data['amount'] ?? []);
            $total = $amount['total'] ?? null;
            $currency = $amount['currency'] ?? 'CNY';

            if ($total === null || $currency !== 'CNY') {
                return $this->failNotify($payment, '支付回调缺少金额或币种不匹配', [
                    'amount' => $amount,
                ]);
            }

            $paidAmount = bcdiv((string) $total, '100', 2);

            if (bccomp($paidAmount, (string) $payment->amount, 2) !== 0) {
                return $this->failNotify($payment, '支付回调金额与支付单不一致', [
                    'callback_amount' => $paidAmount,
                    'payment_amount' => $payment->amount,
                ]);
            }

            if (isset($data['mchid']) && (string) $data['mchid'] !== (string) $wechatPayment->mch_id) {
                return $this->failNotify($payment, '支付回调商户号不匹配', [
                    'mchid' => $data['mchid'],
                ]);
            }

            $appId = $wechatPayment->wechat?->app_id;

            if ($appId && isset($data['appid']) && (string) $data['appid'] !== (string) $appId) {
                return $this->failNotify($payment, '支付回调应用ID不匹配', [
                    'appid' => $data['appid'],
                ]);
            }

            // 标记支付单已支付并推进关联业务（商城订单 / 充值单），与余额支付共用同一出口
            DB::transaction(function () use ($payment, $data, $paidAmount): void {
                service(PaymentService::class)->markPaidWithBusiness(
                    payment: $payment,
                    paymentNo: $data['transaction_id'] ?? null,
                    paidAmount: $paidAmount,
                );
            });

            return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
        } catch (Throwable $e) {
            report($e);

            service(EventLogService::class)->write(
                'payment.notify_failed',
                '支付回调处理失败',
                null,
                $payment,
                $e->getMessage(),
                ['exception' => $e::class]
            );

            // 不回传内部异常信息，避免把 SQL / 验签细节暴露给渠道
            return response()->json(['code' => 'FAIL', 'message' => '支付回调处理失败']);
        }
    }

    /**
     * 微信退款回调
     *
     * 验签后按 refund_status 把支付退款单推进到完成或失败，并同步来源售后单。
     *
     * @param  PaymentRefund  $refund  支付退款单
     *
     * @return JsonResponse 处理结果
     */
    public function refundNotify(PaymentRefund $refund): JsonResponse
    {
        $payment = $refund->paymentOrder;

        if (!$payment || $payment->gateway !== PaymentGateway::Wechat) {
            return response()->json(['code' => 'FAIL', 'message' => '退款单不支持微信回调']);
        }

        try {
            $wechatPayment = WechatPaymentResolver::forPayment($payment, requireEnabled: false);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }

        try {
            $data = service(WechatPaymentService::class)->handleNotify($wechatPayment, requireEnabled: false);

            service(EventLogService::class)->write('refund.notify_received', '收到退款回调', null, $refund, null, [
                'out_refund_no' => $data['out_refund_no'] ?? null,
                'refund_status' => $data['refund_status'] ?? null,
                'refund_id' => $data['refund_id'] ?? null,
            ]);

            if (($data['out_refund_no'] ?? null) !== $refund->no) {
                return $this->failRefundNotify($refund, '回调单号与退款单不匹配', [
                    'out_refund_no' => $data['out_refund_no'] ?? null,
                ]);
            }

            if ($refund->status === PaymentRefundStatus::Completed) {
                return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
            }

            $amount = (array) ($data['amount'] ?? []);
            $refundAmount = $amount['refund'] ?? null;

            if ($refundAmount === null) {
                return $this->failRefundNotify($refund, '退款回调缺少退款金额', [
                    'amount' => $amount,
                ]);
            }

            if (bccomp(bcdiv((string) $refundAmount, '100', 2), (string) $refund->amount, 2) !== 0) {
                return $this->failRefundNotify($refund, '退款回调金额与退款单不一致', [
                    'callback_amount' => bcdiv((string) $refundAmount, '100', 2),
                    'refund_amount' => $refund->amount,
                ]);
            }

            if (isset($data['mchid']) && (string) $data['mchid'] !== (string) $wechatPayment->mch_id) {
                return $this->failRefundNotify($refund, '退款回调商户号不匹配', [
                    'mchid' => $data['mchid'],
                ]);
            }

            $service = service(PaymentRefundService::class);

            match ($data['refund_status'] ?? null) {
                'SUCCESS' => $service->complete($refund),
                'CLOSED', 'ABNORMAL' => $service->failFromGateway($refund, '微信退款关闭或异常：'.$data['refund_status']),
                default => null,
            };

            return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
        } catch (Throwable $e) {
            report($e);

            service(EventLogService::class)->write(
                'refund.notify_failed',
                '退款回调处理失败',
                null,
                $refund,
                $e->getMessage(),
                ['exception' => $e::class]
            );

            return response()->json(['code' => 'FAIL', 'message' => '退款回调处理失败']);
        }
    }

    /**
     * 支付回调失败：写审计并返回 FAIL
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  string  $reason  失败原因
     * @param  array<string, mixed>  $payload  上下文
     *
     * @return JsonResponse 渠道响应
     */
    private function failNotify(PaymentOrder $payment, string $reason, array $payload = []): JsonResponse
    {
        service(EventLogService::class)->write(
            'payment.notify_failed',
            '支付回调校验失败',
            null,
            $payment,
            $reason,
            $payload,
        );

        return response()->json(['code' => 'FAIL', 'message' => $reason]);
    }

    /**
     * 退款回调失败：写审计并返回 FAIL
     *
     * @param  PaymentRefund  $refund  支付退款单
     * @param  string  $reason  失败原因
     * @param  array<string, mixed>  $payload  上下文
     *
     * @return JsonResponse 渠道响应
     */
    private function failRefundNotify(PaymentRefund $refund, string $reason, array $payload = []): JsonResponse
    {
        service(EventLogService::class)->write(
            'refund.notify_failed',
            '退款回调校验失败',
            null,
            $refund,
            $reason,
            $payload,
        );

        return response()->json(['code' => 'FAIL', 'message' => $reason]);
    }
}
