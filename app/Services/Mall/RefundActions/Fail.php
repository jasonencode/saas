<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\RefundStatus;
use App\Events\Mall\RefundFailed;
use App\Models\Mall\Refund;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 标记退款失败（processing → failed）
 *
 * 入口：RefundService::markRefundFailed() / Fail::dispatch()。
 *
 * before：只有退款处理中的退款单可以标记失败
 * handle：状态落库并派发 RefundFailed
 * log   ：写日志中心 refund.failed
 * after ：空
 */
final class Fail extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly string $remark,
    ) {}

    /**
     * 前置：只有退款处理中的退款单可以标记失败
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('只能标记退款处理中的退款单为失败');
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
            // 行锁串行化，避免与支付回调/人工确认并发时覆盖终态
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            if ($this->refund->status !== RefundStatus::Processing) {
                throw new RuntimeException('只能标记退款处理中的退款单为失败');
            }

            $this->refund->update(['status' => RefundStatus::Failed]);

            RefundFailed::dispatch($this->refund, $this->operator);

            return true;
        });
    }

    /**
     * 记录失败日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.failed', '退款失败', $this->operator, $this->refund, $this->remark, [
            'previous_status' => RefundStatus::Processing->value,
            'next_status' => RefundStatus::Failed->value,
            'fail_reason' => $this->remark,
        ]);
    }
}
