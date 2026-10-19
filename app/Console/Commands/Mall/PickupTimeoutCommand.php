<?php

namespace App\Console\Commands\Mall;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\RefundService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

#[Signature('app:mall:pickup-timeout')]
#[Description('自提订单超时未核销自动发起退款')]
#[CommandLabel('自提订单超时退款')]
class PickupTimeoutCommand extends BaseCommand
{
    /**
     * 本次执行中逐条失败的数量
     */
    protected int $failed = 0;

    public function handle(RefundService $service): int
    {
        $days = (int) config('custom.mall.pickup_expired_days', 7);

        if ($days <= 0) {
            $this->info('未启用自提超时退款（custom.mall.pickup_expired_days <= 0）。');

            return self::SUCCESS;
        }

        $this->info("开始扫描超过 {$days} 天未核销的自提订单...");

        $count = 0;

        Order::where('status', OrderStatus::PickupPending)
            ->where('fulfillment_type', FulfillmentType::Pickup)
            ->whereNotNull('paid_at')
            ->where('paid_at', '<=', now()->subDays($days))
            ->chunkById(100, function (Collection $orders) use ($service, &$count): void {
                foreach ($orders as $order) {
                    try {
                        $this->refundOrder($service, $order);
                        $count++;
                        $this->line(sprintf('订单 [%s] 已自动发起退款', $order->no));
                    } catch (Throwable $e) {
                        $this->failed++;
                        $this->error("订单 [$order->no] 自动退款失败: ".$e->getMessage());
                    }
                }
            });

        $this->info("任务执行完毕，共发起 $count 笔自提超时退款。");

        $this->logContext(['refunded' => $count, 'failed' => $this->failed]);

        return self::SUCCESS;
    }

    /**
     * 为单个超时自提订单发起全额仅退款
     *
     * @param  RefundService  $service  退款服务
     * @param  Order  $order  订单
     *
     * @throws Throwable 退款创建或提交失败
     */
    private function refundOrder(RefundService $service, Order $order): void
    {
        if (!$service->isOrderRefundable($order)) {
            return;
        }

        $items = $order->items()
            ->get()
            ->map(static function ($orderItem): ?RefundItemData {
                $qty = $orderItem->getMaxRefundCounts();

                return $qty > 0
                    ? RefundItemData::make($orderItem->id, $qty, '自提超时未核销')
                    : null;
            })
            ->filter()
            ->values()
            ->all();

        if ($items === []) {
            return;
        }

        $refund = $service->createRefund(
            $order,
            $this->user(),
            RefundData::make(
                type: RefundType::OnlyRefund,
                reason: RefundReason::NotReceived,
                reasonDetail: '自提订单超过约定时间未核销，系统自动发起退款',
                items: $items,
            ),
        );

        // 自动进入资金退款，财务确认后原路退回
        $service->confirmRefund($refund, $this->user(), '自提超时自动退款');
    }
}
