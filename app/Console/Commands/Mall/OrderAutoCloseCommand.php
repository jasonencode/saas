<?php

namespace App\Console\Commands\Mall;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * 关闭超时未支付订单
 *
 * 下单时会投递延时队列任务（AutoCloseOrder），本命令作为兜底扫描：
 * 队列丢消息、Redis 被清空、worker 重试耗尽时仍能释放库存与优惠券。
 */
#[Signature('app:mall:order-auto-close')]
#[Description('关闭超时未支付订单')]
#[CommandLabel('关闭超时订单')]
class OrderAutoCloseCommand extends BaseCommand
{
    /**
     * 本次执行中逐条失败的数量
     */
    protected int $failed = 0;

    public function handle(OrderService $service): int
    {
        $this->info('开始执行超时未支付订单扫描...');

        $count = 0;

        Order::where('status', OrderStatus::Pending)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->chunkById(100, function (Collection $orders) use ($service, &$count): void {
                foreach ($orders as $order) {
                    try {
                        // 加锁前复核：可能已被支付/取消
                        $order->refresh();

                        if ($order->status !== OrderStatus::Pending) {
                            continue;
                        }

                        $service->cancel($order, $this->user());
                        $count++;
                        $this->line(sprintf('订单 [%s] 已自动关闭', $order->no));
                    } catch (Throwable $e) {
                        $this->failed++;
                        $this->error("订单 [$order->no] 自动关闭失败: ".$e->getMessage());
                    }
                }
            });

        $this->info("命令执行完成，共关闭 $count 笔超时订单。");

        $this->logContext(['closed' => $count, 'failed' => $this->failed]);

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
