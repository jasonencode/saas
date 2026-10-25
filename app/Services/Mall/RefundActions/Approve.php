<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundActions\Concerns\DeterminesRefundReturn;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 审核通过退款申请（pending → waiting_return / processing）
 *
 * 入口：RefundService::approveRefund() / Approve::dispatch()。
 *
 * before：只有待审核的退款单可以审核
 * handle：按订单状态判断是否需要退货，落库审核人与下一步状态
 * log   ：写日志中心 refund.approved，并补下一步状态日志
 * after ：空
 */
final class Approve extends AbstractOperation
{
    use DeterminesRefundReturn;

    /**
     * 审核后的目标状态（handle 写入、log 读取）
     */
    private ?RefundStatus $nextStatus = null;

    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly ?string $remark = null,
    ) {}

    /**
     * 前置：只有待审核的退款单可以审核
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能审核待审核的退款');
        }
    }

    /**
     * 审核落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        $needsReturn = $this->needsReturn($this->refund->order);

        return DB::transaction(function () use ($needsReturn): bool {
            $this->nextStatus = $needsReturn ? RefundStatus::WaitingReturn : RefundStatus::Processing;

            $this->refund->update([
                'status' => $this->nextStatus,
                'approved_by' => $this->operator->getKey(),
                'approved_at' => now(),
                'approval_remark' => $this->remark,
            ]);

            return true;
        });
    }

    /**
     * 记录审核日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        $eventLog = service(EventLogService::class);

        $eventLog->write('refund.approved', '审核通过', $this->operator, $this->refund, $this->remark, [
            'previous_status' => RefundStatus::Pending->value,
            'next_status' => $this->nextStatus?->value,
            'needs_return' => $this->nextStatus === RefundStatus::WaitingReturn,
        ]);

        if ($this->nextStatus === RefundStatus::WaitingReturn) {
            $eventLog->write('refund.waiting_return', '等待用户退货', $this->operator, $this->refund, null, [
                'status' => RefundStatus::WaitingReturn->value,
            ]);

            return;
        }

        $eventLog->write('refund.processing', '自动进入退款处理', $this->operator, $this->refund, null, [
            'status' => RefundStatus::Processing->value,
        ]);
    }
}
