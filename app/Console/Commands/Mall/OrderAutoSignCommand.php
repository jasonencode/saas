<?php

namespace App\Console\Commands\Mall;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

#[Signature('app:mall:order-auto-sign')]
#[Description('商城订单超时自动签收任务')]
#[CommandLabel('订单自动签收')]
class OrderAutoSignCommand extends BaseCommand
{
    /**
     * 本次执行中逐条失败的数量
     */
    protected int $failed = 0;

    public function handle(OrderService $service): int
    {
        $this->info('开始执行订单自动签收扫描...');

        $count = 0;

        Order::where('status', OrderStatus::Delivered)
            ->where('fulfillment_type', FulfillmentType::Mail)
            ->whereNotNull('auto_sign_at')
            ->where('auto_sign_at', '<=', now())
            ->chunkById(100, function (Collection $orders) use ($service, &$count): void {
                foreach ($orders as $order) {
                    try {
                        // 锁外可能刚被延时签收，签收前再确认一次自动签收时间
                        $order->refresh();

                        if ($order->status !== OrderStatus::Delivered
                            || !$order->auto_sign_at
                            || $order->auto_sign_at->isFuture()) {
                            continue;
                        }

                        $service->sign($order, $this->user());
                        $count++;
                        $this->line(sprintf('订单 [%s] 已自动签收', $order->no));
                    } catch (Throwable $e) {
                        $this->failed++;
                        $this->error("订单 [$order->no] 自动签收失败: ".$e->getMessage());
                    }
                }
            });

        $this->info("任务执行完毕，共自动签收 $count 笔订单。");

        $this->logContext(['signed' => $count, 'failed' => $this->failed]);

        return self::SUCCESS;
    }
}
