<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Refund;
use App\Models\System\System;
use App\Services\Finance\PaymentRefundService;
use App\Services\System\EventLogService;
use RuntimeException;
use Throwable;

/**
 * 极速退款（申请后自动完成审核与原路退回）
 *
 * 入口：RefundService::expressRefund() / Express::dispatch()，由 ProcessExpressRefundJob 触发。
 *
 * handle：创建支付退款单 → 自动审核通过 → 执行原路退回；
 *        通道失败时把售后单置为失败，等待后台重新发起。
 * log   ：写日志中心 refund.express。
 */
final class Express extends AbstractOperation
{
    /**
     * 本次操作的支付退款单（handle 写入、log 读取）
     */
    private ?PaymentRefund $paymentRefund = null;

    /**
     * 操作人（handle 写入、log 读取）
     */
    private ?Authenticatable $operator = null;

    public function __construct(
        private readonly Refund $refund,
    ) {}

    /**
     * 前置：无强制校验，非极速单或状态不允许时由 handle() 跳过
     */
    public function before(): void {}

    /**
     * 自动完成退款；非极速单或状态不允许时跳过
     *
     * @throws Throwable 支付退款通道异常
     */
    public function handle(): bool
    {
        if (!$this->refund->is_express) {
            return false;
        }

        $this->refund->refresh();

        if ($this->refund->status !== RefundStatus::Processing) {
            return false;
        }

        $this->operator = System::query()->find(System::QUEUE_ID) ?? $this->refund->user;

        try {
            Process::dispatch($this->refund, $this->operator);

            $this->paymentRefund = $this->refund->paymentRefunds()->latest('id')->first();

            if (!$this->paymentRefund) {
                throw new RuntimeException('未创建支付退款单，无法自动退款');
            }

            $this->autoApproveAndExecute();
        } catch (Throwable $e) {
            $this->markFailed($e->getMessage());

            throw $e;
        }

        return true;
    }

    /**
     * 记录极速退款日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.express', '极速退款', $this->operator, $this->refund, null, [
            'payment_refund_no' => $this->paymentRefund?->no,
            'payment_refund_status' => $this->paymentRefund?->status?->value,
            'amount' => $this->paymentRefund?->amount,
        ]);
    }

    /**
     * 自动审核并执行支付退款
     *
     * @throws Throwable 支付退款通道异常
     */
    private function autoApproveAndExecute(): void
    {
        $service = service(PaymentRefundService::class);

        if ($this->paymentRefund?->status === PaymentRefundStatus::Pending) {
            $service->autoApprove($this->paymentRefund);
        }

        // 通道处理中或已完成时不再执行，等待回调或人工确认
        if ($this->paymentRefund?->fresh()?->status !== PaymentRefundStatus::Approved) {
            return;
        }

        $service->execute($this->paymentRefund);
    }

    /**
     * 自动退款失败：售后单转为失败，等待后台重新发起
     *
     * @param  string  $reason  失败原因
     */
    private function markFailed(string $reason): void
    {
        if ($this->refund->fresh()->status !== RefundStatus::Processing) {
            return;
        }

        try {
            Fail::dispatch($this->refund, $this->operator, "极速退款失败：{$reason}");
        } catch (Throwable $e) {
            report($e);
        }
    }
}
