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
 * 重试退款（failed → processing）
 *
 * 入口：RefundService::retryRefund() / Retry::dispatch()。
 *
 * before：只有退款失败的退款单可以重试
 * handle：状态回到退款处理中
 * log   ：写日志中心 refund.processing
 * after ：空
 */
final class Retry extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly ?string $remark = null,
    ) {}

    /**
     * 前置：只有退款失败的退款单可以重试
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Failed) {
            throw new RuntimeException('只能重试退款失败的退款单');
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
            // 行锁串行化，避免重复重试
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            if ($this->refund->status !== RefundStatus::Failed) {
                throw new RuntimeException('只能重试退款失败的退款单');
            }

            $this->refund->update(['status' => RefundStatus::Processing]);

            return true;
        });
    }

    /**
     * 记录重试日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write(
            'refund.processing',
            '重试退款',
            $this->operator,
            $this->refund,
            $this->remark,
            [
                'previous_status' => RefundStatus::Failed->value,
                'next_status' => RefundStatus::Processing->value,
            ]
        );
    }
}
