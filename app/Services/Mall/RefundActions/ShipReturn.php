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
 * 用户提交退货物流（waiting_return → shipping）
 *
 * 入口：RefundService::shipReturn() / ShipReturn::dispatch()。
 *
 * before：只有等待退货的退款单可以提交物流
 * handle：写退货物流记录并流转状态
 * log   ：写日志中心 refund.return_shipped
 * after ：空
 */
final class ShipReturn extends AbstractOperation
{
    /**
     * @param  Refund  $refund  退款单
     * @param  Authenticatable  $operator  操作人
     * @param  array{express_id: int|string, express_no: string}  $expressData  物流数据
     */
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly array $expressData,
    ) {}

    /**
     * 前置：只有等待退货的退款单可以提交物流
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::WaitingReturn) {
            throw new RuntimeException('只能在等待退货状态下提交物流信息');
        }
    }

    /**
     * 写退货物流并落库状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->refund->express()->updateOrCreate(
                ['refund_id' => $this->refund->id],
                [
                    'express_id' => $this->expressData['express_id'],
                    'express_no' => $this->expressData['express_no'],
                    'status' => RefundExpressStatus::Shipped,
                    'shipped_at' => now(),
                ]
            );

            $this->refund->update(['status' => RefundStatus::Shipping]);

            return true;
        });
    }

    /**
     * 记录退货发货日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write(
            'refund.return_shipped',
            '退货发货',
            $this->operator,
            $this->refund,
            null,
            [
                'previous_status' => RefundStatus::WaitingReturn->value,
                'next_status' => RefundStatus::Shipping->value,
                'express_id' => $this->expressData['express_id'],
                'express_no' => $this->expressData['express_no'],
            ]
        );
    }
}
