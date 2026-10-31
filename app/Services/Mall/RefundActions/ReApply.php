<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Services\Finance\PaymentRefundService;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 重置退款申请（rejected / cancelled / failed → pending）
 *
 * 入口：RefundService::reApplyRefund() / ReApply::dispatch()。
 *
 * before：只有已拒绝、已取消或退款失败的退款单可以重置，且商品仍有可退数量
 * handle：锁订单与退款单后复核，清空审核/退款时间并回到待审核
 * log   ：写日志中心 refund.reapplied
 * after ：空
 *
 * 只重置退款单，不改变原订单状态；重置后需重新审核。
 */
final class ReApply extends AbstractOperation
{
    /**
     * 可重置的状态
     *
     * @var array<int, RefundStatus>
     */
    private const array REAPPLICABLE = [
        RefundStatus::Rejected,
        RefundStatus::Cancelled,
        RefundStatus::Failed,
    ];

    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly ?string $remark = null,
    ) {}

    /**
     * 前置：状态与可退数量校验
     */
    public function before(): void
    {
        $this->assertReapplicable($this->refund);
        $this->assertItemsRefundable();
    }

    /**
     * 重置为待审核
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 先锁订单：与新的退款申请串行，避免重置后与另一笔申请重复占用数量
            Order::whereKey($this->refund->order_id)->lockForUpdate()->first();
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            $this->assertReapplicable($this->refund);
            $this->assertItemsRefundable();

            $this->refund->update([
                'status' => RefundStatus::Pending,
                'approved_by' => null,
                'approved_at' => null,
                'approval_remark' => null,
                'refund_at' => null,
            ]);

            return true;
        });
    }

    /**
     * 记录重置申请日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.reapplied', '重置退款申请', $this->operator, $this->refund, $this->remark, [
            'next_status' => RefundStatus::Pending->value,
        ]);
    }

    /**
     * 状态校验（before 与锁内复核共用）
     */
    private function assertReapplicable(Refund $refund): void
    {
        if (!in_array($refund->status, self::REAPPLICABLE, true)) {
            throw new RuntimeException('当前退款单状态不可重置为待审核');
        }

        // 同一订单已有处理中的退款时不允许重置，避免两笔退款同时占用商品数量
        $hasActiveRefund = $refund->order->refunds()
            ->where('id', '!=', $refund->getKey())
            ->whereIn('status', RefundStatus::activeCases())
            ->exists();

        if ($hasActiveRefund) {
            throw new RuntimeException('该订单已有处理中的退款申请');
        }

        // 已存在支付退款占用的退款单不允许重置，避免重置后重复发起打款
        $hasOccupiedPaymentRefund = $refund->paymentRefunds()
            ->whereIn('status', PaymentRefundService::OCCUPIED_STATUSES)
            ->exists();

        if ($hasOccupiedPaymentRefund) {
            throw new RuntimeException('该退款单已有支付退款记录，无法重置申请');
        }
    }

    /**
     * 校验退款商品仍有可退数量
     *
     * @throws RuntimeException 商品可退数量不足
     */
    private function assertItemsRefundable(): void
    {
        $items = $this->refund->items()->with('orderItem.orderable')->get();

        foreach ($items as $item) {
            $orderItem = $item->orderItem;

            if (!$orderItem) {
                throw new RuntimeException('退款商品不存在，无法重新申请');
            }

            if ($item->qty > $orderItem->getMaxRefundCounts()) {
                throw new RuntimeException(sprintf(
                    '商品「%s」可退数量不足，无法重新申请',
                    $orderItem->orderable?->getOrderableName() ?? $orderItem->orderable_name,
                ));
            }
        }
    }
}
