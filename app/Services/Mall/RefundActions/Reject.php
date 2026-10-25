<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 审核驳回退款申请（pending → rejected）
 *
 * 入口：RefundService::rejectRefund() / Reject::dispatch()。
 *
 * before：只有待审核的退款单可以驳回
 * handle：状态、审核人与驳回原因落库
 * log   ：写日志中心 refund.rejected
 * after ：空
 */
final class Reject extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly string $remark,
    ) {}

    /**
     * 前置：只有待审核的退款单可以驳回
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能审核待审核的退款');
        }
    }

    /**
     * 驳回落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->refund->update([
                'status' => RefundStatus::Rejected,
                'approved_by' => $this->operator->getKey(),
                'approved_at' => now(),
                'approval_remark' => $this->remark,
            ]);

            return true;
        });
    }

    /**
     * 记录驳回日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.rejected', '审核拒绝', $this->operator, $this->refund, $this->remark, [
            'previous_status' => RefundStatus::Pending->value,
            'next_status' => RefundStatus::Rejected->value,
            'reject_reason' => $this->remark,
        ]);
    }
}
