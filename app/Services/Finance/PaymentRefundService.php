<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\AccountAssetType;
use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\User\UserAccountLogType;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Finance\UserAccount;
use App\Models\Foundation\WechatPayment;
use App\Services\Foundation\WechatPaymentService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 支付退款单服务
 *
 * 支付侧退款闭环：申请（Pending）→ 财务审核（Approved / Rejected）→ 原路退回
 * （Processing → Completed / Failed）→ 失败可重试。退款一律走原支付通道：
 * 微信退回微信，余额退回用户余额。
 */
class PaymentRefundService implements ServiceInterface
{
    /**
     * 计入已退款金额的状态（申请中与已完成都占用可退额度）
     */
    public const array OCCUPIED_STATUSES = [
        PaymentRefundStatus::Pending,
        PaymentRefundStatus::Approved,
        PaymentRefundStatus::Processing,
        PaymentRefundStatus::Completed,
    ];

    /**
     * 计算支付单剩余可退金额
     *
     * @param  PaymentOrder  $payment  支付单
     *
     * @return string 可退金额（保留两位小数）
     */
    public function refundableAmount(PaymentOrder $payment): string
    {
        $refunded = $payment->refunds()
            ->whereIn('status', self::OCCUPIED_STATUSES)
            ->sum('amount');

        return bcsub((string) $payment->amount, (string) $refunded, 2);
    }

    /**
     * 创建退款申请
     *
     * 仅已支付的支付单可申请；金额必须在剩余可退额度内（含审核中与处理中的占用）。
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  float  $amount  退款金额
     * @param  string|null  $reason  退款原因
     * @param  Model|null  $creator  申请人（用户或后台管理员）
     * @param  Model|null  $source  来源单据（如商城售后单）
     *
     * @throws InvalidArgumentException 支付单未支付或金额超出可退额度
     * @throws Throwable
     *
     * @return PaymentRefund 创建的退款单
     */
    public function create(
        PaymentOrder $payment,
        float $amount,
        ?string $reason = null,
        ?Model $creator = null,
        ?Model $source = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): PaymentRefund {
        if ($payment->status !== PaymentStatus::Paid) {
            throw new InvalidArgumentException('该订单未支付，无法申请退款');
        }

        if (bccomp((string) $amount, '0.01', 2) < 0) {
            throw new InvalidArgumentException('退款金额不能小于 0.01');
        }

        if (bccomp((string) $amount, $this->refundableAmount($payment), 2) === 1) {
            throw new InvalidArgumentException('该订单可退款金额不足');
        }

        $attributes = [
            'tenant_id' => $payment->tenant_id,
            'payment_order_id' => $payment->getKey(),
            'amount' => $amount,
            'reason' => $reason,
            'status' => PaymentRefundStatus::Pending,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ];

        if ($creator) {
            $attributes['creator'] = $creator;
        }

        if ($source) {
            $attributes['source'] = $source;
        }

        return PaymentRefund::create($attributes);
    }

    /**
     * 审核通过
     *
     * @param  PaymentRefund  $refund  退款单
     * @param  int  $approverId  审核人（后台用户）ID
     *
     * @throws InvalidArgumentException 非待审核状态
     * @throws Throwable
     */
    public function approve(PaymentRefund $refund, int $approverId): void
    {
        $affected = PaymentRefund::query()
            ->whereKey($refund->getKey())
            ->where('status', PaymentRefundStatus::Pending)
            ->update([
                'status' => PaymentRefundStatus::Approved,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

        if ($affected === 0) {
            throw new InvalidArgumentException('只能审核待处理的退款单');
        }

        $refund->refresh();
    }

    /**
     * 审核驳回
     *
     * @param  PaymentRefund  $refund  退款单
     * @param  int  $approverId  审核人（后台用户）ID
     * @param  string  $reason  驳回原因
     *
     * @throws InvalidArgumentException 非待审核状态
     * @throws Throwable
     */
    public function reject(PaymentRefund $refund, int $approverId, string $reason): void
    {
        $affected = PaymentRefund::query()
            ->whereKey($refund->getKey())
            ->where('status', PaymentRefundStatus::Pending)
            ->update([
                'status' => PaymentRefundStatus::Rejected,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejected_reason' => $reason,
            ]);

        if ($affected === 0) {
            throw new InvalidArgumentException('只能审核待处理的退款单');
        }

        $refund->refresh();
    }

    /**
     * 取消退款申请（用户自助）
     *
     * @param  PaymentRefund  $refund  退款单
     *
     * @throws InvalidArgumentException 非待审核状态
     * @throws Throwable
     */
    public function cancel(PaymentRefund $refund): void
    {
        $affected = PaymentRefund::query()
            ->whereKey($refund->getKey())
            ->where('status', PaymentRefundStatus::Pending)
            ->update(['status' => PaymentRefundStatus::Cancelled]);

        if ($affected === 0) {
            throw new InvalidArgumentException('只能取消待审核的退款单');
        }

        $refund->refresh();
    }

    /**
     * 执行退款（原路退回）
     *
     * 以「Approved → Processing」的条件更新抢占执行权，避免并发重复发起退款；
     * 通道调用在事务之外，失败时先落 Failed（含失败原因）再抛出，便于动作层提示与重试。
     *
     * @param  PaymentRefund  $refund  退款单
     *
     * @throws InvalidArgumentException 非审核通过状态
     * @throws Throwable 通道退款失败
     */
    public function execute(PaymentRefund $refund): void
    {
        $affected = PaymentRefund::query()
            ->whereKey($refund->getKey())
            ->where('status', PaymentRefundStatus::Approved)
            ->update([
                'status' => PaymentRefundStatus::Processing,
                'failed_reason' => null,
            ]);

        if ($affected === 0) {
            throw new InvalidArgumentException('只能执行审核通过的退款单');
        }

        $refund->refresh();

        $payment = $refund->paymentOrder;

        if (!$payment) {
            $this->markFailed($refund, '退款单未关联支付单');

            throw new RuntimeException('退款单未关联支付单');
        }

        try {
            $result = $this->refundByGateway($payment, $refund);
        } catch (Throwable $e) {
            $this->markFailed($refund, $e->getMessage());

            throw $e;
        }

        $refund->update([
            'status' => $result['completed'] ? PaymentRefundStatus::Completed : PaymentRefundStatus::Processing,
            'channel_refund_no' => $result['channel_no'],
            'refunded_at' => $result['completed'] ? now() : null,
        ]);
    }

    /**
     * 重试退款
     *
     * 仅失败状态可重试，重试即重新执行原路退回。
     *
     * @param  PaymentRefund  $refund  退款单
     *
     * @throws InvalidArgumentException 非失败状态
     * @throws Throwable
     */
    public function retry(PaymentRefund $refund): void
    {
        $affected = PaymentRefund::query()
            ->whereKey($refund->getKey())
            ->where('status', PaymentRefundStatus::Failed)
            ->update([
                'status' => PaymentRefundStatus::Approved,
                'failed_reason' => null,
            ]);

        if ($affected === 0) {
            throw new InvalidArgumentException('只能重试退款失败的退款单');
        }

        $refund->refresh();

        $this->execute($refund);
    }

    /**
     * 标记退款失败
     *
     * @param  PaymentRefund  $refund  退款单
     * @param  string  $reason  失败原因
     */
    private function markFailed(PaymentRefund $refund, string $reason): void
    {
        $refund->update([
            'status' => PaymentRefundStatus::Failed,
            'failed_reason' => $reason,
        ]);

        $refund->refresh();
    }

    /**
     * 按原支付通道退回款项
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  PaymentRefund  $refund  退款单
     *
     * @throws RuntimeException 通道不支持或配置缺失
     *
     * @return array{channel_no: string|null, completed: bool} 通道退款单号与是否已到账
     */
    private function refundByGateway(PaymentOrder $payment, PaymentRefund $refund): array
    {
        return match ($payment->gateway) {
            PaymentGateway::Wechat => $this->refundToWechat($payment, $refund),
            PaymentGateway::Balance => [
                'channel_no' => null,
                'completed' => $this->refundToBalance($payment, $refund),
            ],
            default => throw new RuntimeException(
                sprintf('支付方式「%s」暂不支持自动原路退回，请人工处理', $payment->gateway->getLabel())
            ),
        };
    }

    /**
     * 微信退款（原路退回）
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  PaymentRefund  $refund  退款单
     *
     * @throws RuntimeException 未配置微信支付或通道未受理
     *
     * @return array{channel_no: string|null, completed: bool} 微信退款单号与是否已退款成功
     */
    private function refundToWechat(PaymentOrder $payment, PaymentRefund $refund): array
    {
        // 优先取启用的商户配置；均已停用时仍沿用原配置退款（原路退回不依赖启用开关）
        $config = WechatPayment::ofTenant($payment->tenant_id)
            ->orderByDesc('status')
            ->first();

        if (!$config) {
            throw new RuntimeException('未配置微信支付，无法原路退回');
        }

        $result = service(WechatPaymentService::class)->refund(
            payment: $config,
            outTradeNo: $payment->no,
            outRefundNo: $refund->no,
            refundAmount: (int) bcmul((string) $refund->amount, '100', 0),
            totalAmount: (int) bcmul((string) $payment->amount, '100', 0),
            reason: $refund->reason,
        );

        $status = $result['status'] ?? null;

        if (!in_array($status, ['SUCCESS', 'PROCESSING'], true)) {
            throw new RuntimeException('微信退款未受理：'.json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        return [
            'channel_no' => $result['refund_id'] ?? null,
            // PROCESSING 表示微信侧异步处理中，保持 Processing 状态等待人工核对
            'completed' => $status === 'SUCCESS',
        ];
    }

    /**
     * 余额退款（退回用户余额）
     *
     * @param  PaymentOrder  $payment  支付单
     * @param  PaymentRefund  $refund  退款单
     *
     * @throws RuntimeException 用户账户不存在
     *
     * @return bool 是否已到账
     */
    private function refundToBalance(PaymentOrder $payment, PaymentRefund $refund): bool
    {
        $account = UserAccount::find($payment->user_id);

        if (!$account) {
            throw new RuntimeException('用户账户不存在，无法退回余额');
        }

        service(UserAccountService::class)->modifyAsset(
            account: $account,
            asset: AccountAssetType::Balance,
            amount: (float) $refund->amount,
            remark: "退款单号# {$refund->no}",
            source: $refund,
            type: UserAccountLogType::Refund,
        );

        return true;
    }
}
