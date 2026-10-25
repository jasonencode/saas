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
 * 取消退款申请（pending → cancelled）
 *
 * 入口：RefundService::cancelRefund() / Cancel::dispatch()。
 *
 * before：只有待审核的退款单可以取消
 * handle：状态落库
 * log   ：写日志中心 refund.canceled
 * after ：空
 */
final class Cancel extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：只有待审核的退款单可以取消
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Pending) {
            throw new RuntimeException('只能取消待审核的退款');
        }
    }

    /**
     * 状态落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->refund->update([
                'status' => RefundStatus::Cancelled,
            ]);

            return true;
        });
    }

    /**
     * 记录取消日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.canceled', '取消退款', $this->operator, $this->refund, null, [
            'previous_status' => RefundStatus::Pending->value,
            'next_status' => RefundStatus::Cancelled->value,
        ]);
    }
}
