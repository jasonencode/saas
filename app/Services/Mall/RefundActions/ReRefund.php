<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Refund;
use App\Services\Finance\PaymentRefundService;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 重新发起退款（processing / failed → 重试支付退款）
 *
 * 入口：RefundService::reRefund() / ReRefund::dispatch()。
 *
 * before：只有退款处理中或退款失败的退款单可以重试
 * handle：
 *   - 支付退款单已成功：补完成售后单（复用 Complete，回收资源并推进订单状态）
 *   - 支付退款单失败：先把售后单置回处理中，再重试原支付通道
 *   - 支付退款单仍在处理中：拒绝重复发起
 * log   ：写日志中心 refund.retried
 * after ：空
 *
 * 与 RefundActions\Retry 的区别：Retry 只把失败的售后单重置为处理中（不碰支付通道），
 * 本动作会真正重试支付退款单。
 */
final class ReRefund extends AbstractOperation
{
    /**
     * 本次重试的支付退款单（handle 写入、log 读取）
     */
    private ?PaymentRefund $paymentRefund = null;

    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：只有退款处理中或退款失败的退款单可以重试
     */
    public function before(): void
    {
        $this->assertRetryable($this->refund);
    }

    /**
     * 补完成或重试支付退款
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        $paymentRefund = DB::transaction(function (): PaymentRefund {
            // 行锁串行化，避免并发点击重复调用支付通道
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            $this->assertRetryable($this->refund);

            $paymentRefund = $this->refund->paymentRefunds()->latest('id')->first();

            if (!$paymentRefund) {
                throw new RuntimeException('未找到支付退款单，请先发起退款');
            }

            if ($paymentRefund->status === PaymentRefundStatus::Completed) {
                // 补完成由 Complete 统一处理；先把失败单据置回处理中以满足其流转条件
                if ($this->refund->status === RefundStatus::Failed) {
                    $this->refund->update(['status' => RefundStatus::Processing]);
                }

                return $paymentRefund;
            }

            if ($paymentRefund->status === PaymentRefundStatus::Failed) {
                // 置回处理中，重试成功后支付回调可直接完成售后单
                if ($this->refund->status === RefundStatus::Failed) {
                    $this->refund->update(['status' => RefundStatus::Processing]);
                }

                return $paymentRefund;
            }

            if (in_array($paymentRefund->status, [PaymentRefundStatus::Rejected, PaymentRefundStatus::Cancelled], true)) {
                throw new RuntimeException('支付退款单已关闭，无法重新退款');
            }

            throw new RuntimeException('支付退款单正在处理中，请勿重复发起');
        });

        $this->paymentRefund = $paymentRefund;

        // 支付侧已成功：补完成售后单（回收库存/权益、推进订单状态）
        if ($paymentRefund->status === PaymentRefundStatus::Completed) {
            Complete::dispatch($this->refund, $this->operator, '支付退款已成功，补完成退款');
            $this->refund->refresh();

            return true;
        }

        try {
            service(PaymentRefundService::class)->retry($paymentRefund);
        } catch (Throwable $e) {
            // 通道仍失败：售后单回到失败，等待下一次重试
            Refund::whereKey($this->refund->getKey())
                ->where('status', RefundStatus::Processing)
                ->update(['status' => RefundStatus::Failed]);

            $this->refund->refresh();

            throw $e;
        }

        $this->refund->refresh();

        return true;
    }

    /**
     * 记录重试日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.retried', '重新发起退款', $this->operator, $this->refund, null, [
            'status' => $this->refund->status->value,
            'payment_refund_no' => $this->paymentRefund?->no,
            'payment_refund_status' => $this->paymentRefund?->status?->value,
        ]);
    }

    /**
     * 状态校验（before 与锁内复核共用）
     */
    private function assertRetryable(Refund $refund): void
    {
        if (!in_array($refund->status, [RefundStatus::Processing, RefundStatus::Failed], true)) {
            throw new RuntimeException('当前退款单状态不可重新退款');
        }
    }
}
