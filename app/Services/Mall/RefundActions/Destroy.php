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
 * 删除退款单（rejected / cancelled → 软删）
 *
 * 入口：RefundService::destroyRefund() / Destroy::dispatch()。
 *
 * before：只有已拒绝或已取消的退款单可以删除
 * handle：加锁复核后软删退款单
 * log   ：写日志中心 refund.destroyed
 * after ：空
 *
 * 已拒绝 / 已取消不占用商品可退数量，删除后无需重算。
 */
final class Destroy extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：只有已拒绝或已取消的退款单可以删除
     */
    public function before(): void
    {
        $this->assertDeletable($this->refund);
    }

    /**
     * 软删退款单
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 行锁串行化，避免与取消/审核并发时删掉刚流转的单据
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            $this->assertDeletable($this->refund);

            $this->refund->delete();

            return true;
        });
    }

    /**
     * 记录删除日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('refund.destroyed', '删除退款单', $this->operator, $this->refund, null, [
            'no' => $this->refund->no,
            'status' => $this->refund->status->value,
        ]);
    }

    /**
     * 状态校验（before 与锁内复核共用）
     */
    private function assertDeletable(Refund $refund): void
    {
        if (!in_array($refund->status, [RefundStatus::Rejected, RefundStatus::Cancelled], true)) {
            throw new RuntimeException('当前退款单状态不可删除');
        }
    }
}
