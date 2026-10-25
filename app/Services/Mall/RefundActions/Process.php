<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Refund;
use App\Services\Finance\PaymentRefundService;
use App\Services\System\EventLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 发起退款（processing → 支付退款单）
 *
 * 入口：RefundService::processRefund() / Process::dispatch()。
 *
 * before：只有退款处理中的退款单可以发起
 * handle：创建支付退款单（待财务审核后按原支付通道退回），实付退款单保持处理中
 * log   ：写日志中心 refund.processing
 * after ：空
 *
 * 资金实际退回由支付侧执行；支付退款成功后由监听器回调 RefundService::confirmRefund()。
 */
final class Process extends AbstractOperation
{
    /**
     * 本次创建的支付退款单（handle 写入、log 读取）
     */
    private ?PaymentRefund $paymentRefund = null;

    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：只有退款处理中的退款单可以发起
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('只能对退款处理中的退款单发起退款');
        }
    }

    /**
     * 创建支付退款单
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 行锁串行化，避免并发重复创建支付退款单
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            if ($this->refund->status !== RefundStatus::Processing) {
                throw new RuntimeException('只能对退款处理中的退款单发起退款');
            }

            // 幂等：已有进行中的支付退款单时直接返回，避免重复打款
            if ($this->refund->paymentRefunds()->whereIn('status', PaymentRefundService::OCCUPIED_STATUSES)->exists()) {
                return false;
            }

            $this->paymentRefund = $this->createPaymentRefund();

            return true;
        });
    }

    /**
     * 记录发起退款日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.processing', '发起退款', $this->operator, $this->refund, null, [
            'status' => RefundStatus::Processing->value,
            'payment_refund_no' => $this->paymentRefund?->no,
            'amount' => $this->paymentRefund?->amount,
        ]);
    }

    /**
     * 创建支付退款单
     *
     * 退款资金由支付侧执行（微信原路退回 / 余额退回用户账户），本方法只登记待审核的退款单；
     * 找不到可退款的支付单时抛异常，避免出现「售后单已退款完成但资金未退」的假象。
     *
     * @throws RuntimeException 订单没有可退款的支付单
     * @throws Throwable
     */
    private function createPaymentRefund(): PaymentRefund
    {
        $refundService = service(PaymentRefundService::class);
        $amount = (string) $this->refund->total;

        $payment = $this->refund->order->paymentOrders()
            ->where('status', PaymentStatus::Paid)
            ->latest('id')
            ->get()
            ->first(static fn (PaymentOrder $payment): bool => bccomp(
                $refundService->refundableAmount($payment),
                $amount,
                2,
            ) >= 0);

        if (!$payment) {
            throw new RuntimeException('该订单没有可退款的支付单，无法完成退款');
        }

        return $refundService->create(
            payment: $payment,
            amount: (float) $amount,
            reason: "商品退款 #{$this->refund->no}",
            creator: $this->operator instanceof Model ? $this->operator : null,
            source: $this->refund,
        );
    }
}
