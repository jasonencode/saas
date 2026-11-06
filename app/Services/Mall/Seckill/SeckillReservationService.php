<?php

namespace App\Services\Mall\Seckill;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\SeckillRecordStatus;
use App\Models\Campaign\SeckillRecord;
use App\Models\Mall\Order;

/**
 * 秒杀名额占用与回补
 */
class SeckillReservationService implements ServiceInterface
{
    public function __construct(private readonly RedisSeckillStockCounter $counter) {}

    /**
     * 下单成功后把 reserved 记录绑定到订单
     */
    public function markOrdered(SeckillRecord $record, Order $order): void
    {
        $record->update([
            'order_id' => $order->getKey(),
            'status' => SeckillRecordStatus::Ordered,
        ]);
    }

    /**
     * 取消 / 超时关闭：回补名额
     */
    public function releaseForOrder(Order $order): void
    {
        $this->release($order, SeckillRecordStatus::Cancelled);
    }

    /**
     * 全额退款完成：回补名额（可通过配置关闭）
     */
    public function releaseForRefund(Order $order): void
    {
        if (!config('seckill.restore_on_refund', true)) {
            return;
        }

        $this->release($order, SeckillRecordStatus::Refunded);
    }

    /**
     * 按记录状态幂等回补
     */
    private function release(Order $order, SeckillRecordStatus $status): void
    {
        SeckillRecord::query()
            ->where('order_id', $order->getKey())
            ->whereIn('status', [
                SeckillRecordStatus::Reserved->value,
                SeckillRecordStatus::Ordered->value,
            ])
            ->get()
            ->each(fn (SeckillRecord $record) => $this->counter->release($record, $status));
    }
}
