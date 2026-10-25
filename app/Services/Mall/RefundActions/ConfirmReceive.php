<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\RefundExpressStatus;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 商户确认签收退货（shipping → processing）
 *
 * 入口：RefundService::confirmReceive() / ConfirmReceive::dispatch()。
 *
 * before：只有退货中的退款单可以签收
 * handle：回写物流签收时间并进入退款处理
 * log   ：写日志中心 refund.return_received / refund.processing
 * after ：空
 */
final class ConfirmReceive extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly ?string $remark = null,
    ) {}

    /**
     * 前置：只有退货中的退款单可以签收
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Shipping) {
            throw new RuntimeException('只能确认退货中的退款单');
        }
    }

    /**
     * 回写物流签收时间并落库状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 行锁串行化，避免重复签收
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            if ($this->refund->status !== RefundStatus::Shipping) {
                throw new RuntimeException('只能确认退货中的退款单');
            }

            $this->refund->express()->update([
                'status' => RefundExpressStatus::Received,
                'received_at' => now(),
            ]);

            $this->refund->update(['status' => RefundStatus::Processing]);

            return true;
        });
    }

    /**
     * 记录签收日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        $eventLog = service(EventLogService::class);

        $eventLog->write(
            'refund.return_received',
            '签收退货商品',
            $this->operator,
            $this->refund,
            $this->remark,
            [
                'previous_status' => RefundStatus::Shipping->value,
                'next_status' => RefundStatus::Processing->value,
            ]
        );

        $eventLog->write('refund.processing', '签收后自动进入退款处理', $this->operator, $this->refund, null, [
            'status' => RefundStatus::Processing->value,
        ]);
    }
}
