<?php

namespace App\Console\Commands\Mall;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\StoreConfigure;
use App\Services\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:mall:order-auto-complete')]
#[Description('商城订单超时自动完成任务')]
class OrderAutoCompleteCommand extends Command
{
    public function handle(OrderService $service): void
    {
        $this->info('开始执行订单自动完成扫描...');

        // 获取所有有特殊配置的租户自动完成天数，如果没有配置则使用默认值 7
        $configs = StoreConfigure::pluck('auto_complete_days', 'tenant_id');

        // 查询所有已签收且未完成的订单
        $orders = Order::where('status', OrderStatus::Signed)
            ->whereNotNull('signed_at')
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            $days = $configs[$order->tenant_id] ?? 7;

            // 如果签收时间 + 配置天数 <= 当前时间，则执行完成操作
            if ($order->signed_at->addDays($days)->isPast()) {
                try {
                    $service->complete($order);
                    $count++;
                    $this->line("订单 [$order->no] 已自动完成（签收时间：{$order->signed_at}，配置天数：{$days}）");
                } catch (Throwable $e) {
                    $this->error("订单 [$order->no] 自动完成失败: ".$e->getMessage());
                    Log::error("Order AutoComplete Error [$order->no]: ".$e->getMessage());
                }
            }
        }

        $this->info("任务执行完毕，共自动完成 $count 笔订单。");
    }
}
