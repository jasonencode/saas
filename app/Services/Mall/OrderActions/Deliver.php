<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderDelivered;
use App\Events\Mall\OrderPartiallyShipped;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 订单发货（paid / preparing / partially → delivered / partially）
 *
 * 入口：OrderService::deliver() / Deliver::dispatch()。
 *
 * before：状态预校验（handle 内加锁复核）
 * handle：写运单、回写明细发货记录、状态落库并派发发货事件
 * log   ：写日志中心 order.delivered / order.delivering
 * after ：空
 */
final class Deliver extends AbstractOperation
{
    use AssertsOrderState;

    /**
     * @param  Order  $order  订单
     * @param  int[]  $itemIds  发货的商品明细 ID 列表
     * @param  int  $expressId  快递公司 ID
     * @param  string  $expressNo  快递单号
     * @param  Authenticatable  $operator  操作人
     */
    public function __construct(
        private readonly Order $order,
        private readonly array $itemIds,
        private readonly int $expressId,
        private readonly string $expressNo,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：状态预校验
     */
    public function before(): void
    {
        $this->assertCan($this->order, OrderStatus::Delivered);
    }

    /**
     * 写运单并落库状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->assertCanWithLock($this->order, OrderStatus::Delivered);

            $items = $this->order->items()
                ->whereIn('id', $this->itemIds)
                ->with('refundItems.refund')
                ->get();
            if ($items->isEmpty()) {
                throw new InvalidArgumentException('未选择发货商品');
            }

            // 已发货商品不可重复发货（重复操作会覆盖物流绑定，产生空物流单）
            if ($items->contains(fn (OrderItem $item): bool => $item->order_shipping_id !== null)) {
                throw new RuntimeException('所选商品已发货，请勿重复发货');
            }

            // 已全部退款的商品不再发货，避免「退款后仍出库」
            if ($items->contains(fn (OrderItem $item): bool => $item->getShippableQtyAttribute() <= 0)) {
                throw new RuntimeException('所选商品已全部退款，无法发货');
            }

            $expressNo = trim($this->expressNo);

            if ($expressNo === '' || mb_strlen($expressNo) > 32) {
                throw new InvalidArgumentException('物流单号不能为空且不能超过 32 个字符');
            }

            if (!Express::whereKey($this->expressId)->exists()) {
                throw new InvalidArgumentException('物流公司不存在');
            }

            $express = $this->order->shippings()->create([
                'express_id' => $this->expressId,
                'express_no' => $expressNo,
                'delivery_at' => now(),
            ]);

            if ($this->order->address) {
                $express->setAddress($this->order->address);
            }

            foreach ($items as $item) {
                // 只发「未发且未退」的数量：部分退款后不能整行发出
                $item->update([
                    'order_shipping_id' => $express->id,
                    'shipped_qty' => (int) $item->shipped_qty + $item->getShippableQtyAttribute(),
                ]);
            }

            // 完成判定按数量：已发 + 已退 覆盖全部购买数量才算发货完成
            $orderItems = $this->order->items()
                ->with('refundItems.refund')
                ->get();

            $allShipped = $orderItems->every(
                fn (OrderItem $item): bool => $item->resolveShippedQty() + $item->getRefundedQtyAttribute() >= (int) $item->qty
            );

            $this->order->status = $allShipped
                ? OrderStatus::Delivered
                : OrderStatus::PartiallyShipped;

            // 全部发货后登记自动签收时间，超时未签收由 app:mall:order-auto-sign 命令处理
            if ($this->order->status === OrderStatus::Delivered) {
                $this->order->auto_sign_at = now()->addDays((int) config('custom.mall.auto_sign_days', 7));
            }

            $this->order->save();

            if ($this->order->status === OrderStatus::Delivered) {
                OrderDelivered::dispatch($this->order, $this->operator);
            } else {
                OrderPartiallyShipped::dispatch($this->order, $this->operator);
            }

            return true;
        });
    }

    /**
     * 记录发货日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        $delivered = $this->order->status === OrderStatus::Delivered;

        service(EventLogService::class)->write(
            $delivered ? 'order.delivered' : 'order.delivering',
            $delivered ? '订单发货' : '部分发货',
            $this->operator,
            $this->order,
            null,
            [
                'express_id' => $this->expressId,
                'express_no' => $this->expressNo,
                'items_count' => count($this->itemIds),
                'status' => $this->order->status->value,
            ]
        );
    }
}
