<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use App\Services\System\EventLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 取消发货（partially / delivered → paid / partially）
 *
 * 入口：OrderService::cancelDeliver() / CancelDeliver::dispatch()。
 *
 * before：只有部分发货或已发货订单可以取消，且存在可取消的发货记录
 * handle：加锁重读订单与发货记录，解除商品与运单绑定并软删运单，按剩余发货情况回退订单状态
 * log   ：写日志中心 order.deliver_canceled
 * after ：空
 *
 * 未传发货记录集合时取消订单的全部发货记录；传入时只取消指定记录，
 * 便于「按运单取消」与「整单撤回」复用同一个动作。
 */
final class CancelDeliver extends AbstractOperation
{
    /**
     * 本次已取消的发货记录 ID（handle 写入、log 读取）
     *
     * @var array<int, int>
     */
    private array $canceledShippingIds = [];

    /**
     * @param  Order  $order  订单
     * @param  Authenticatable  $operator  操作人（后台管理员）
     * @param  Collection<int, OrderShipping>|null  $shippings  指定要取消的发货记录，null 表示全部
     */
    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
        private readonly ?Collection $shippings = null,
    ) {}

    /**
     * 前置：状态与可取消发货记录校验
     */
    public function before(): void
    {
        $this->assertCancelable($this->order);

        if ($this->targetShippingIds() === []) {
            throw new RuntimeException('订单无可取消的发货记录');
        }
    }

    /**
     * 解除绑定并回退订单状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            Order::whereKey($this->order->getKey())->lockForUpdate()->first();
            $this->order->refresh();

            $this->assertCancelable($this->order);

            // 物流记录按主键升序加锁，与重新发货/修改物流保持同一加锁顺序
            $shippings = $this->order->shippings()
                ->whereIn('id', $this->targetShippingIds())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($shippings->isEmpty()) {
                throw new RuntimeException('订单无可取消的发货记录');
            }

            // 记下真实删除的记录，供日志使用（删除后再查会因软删查不到）
            $this->canceledShippingIds = $shippings->pluck('id')->map(static fn ($id): int => (int) $id)->all();

            foreach ($shippings as $shipping) {
                $this->order->items()
                    ->where('order_shipping_id', $shipping->getKey())
                    ->update(['order_shipping_id' => null]);

                $shipping->delete();
            }

            $this->syncOrderStatus();

            return true;
        });
    }

    /**
     * 记录取消发货日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.deliver_canceled', '取消发货', $this->operator, $this->order, null, [
            'shipping_ids' => $this->canceledShippingIds,
            'status' => $this->order->status->value,
        ]);
    }

    /**
     * 状态校验（before 与锁内复核共用）
     */
    private function assertCancelable(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::PartiallyShipped, OrderStatus::Delivered], true)) {
            throw new RuntimeException('当前订单状态不可取消发货');
        }
    }

    /**
     * 本次要取消的发货记录 ID
     *
     * @return array<int, int>
     */
    private function targetShippingIds(): array
    {
        $ids = $this->shippings?->pluck('id')->all()
            ?? $this->order->shippings()->pluck('id')->all();

        return array_values(array_map(static fn ($id): int => (int) $id, $ids));
    }

    /**
     * 按剩余发货情况回退订单状态
     *
     * 全部撤回时退回「待发货」并清空自动签收时间，等待重新发货后重新计算。
     */
    private function syncOrderStatus(): void
    {
        $totalItems = $this->order->items()->count();
        $shippedItems = $this->order->items()->whereNotNull('order_shipping_id')->count();

        if ($shippedItems === 0) {
            $this->order->status = OrderStatus::Paid;
            $this->order->auto_sign_at = null;
        } elseif ($shippedItems < $totalItems) {
            $this->order->status = OrderStatus::PartiallyShipped;
        } else {
            $this->order->status = OrderStatus::Delivered;
            $this->order->auto_sign_at ??= now()->addDays((int) config('custom.mall.auto_sign_days', 7));
        }

        $this->order->save();
    }
}
