<?php

namespace App\Console\Commands\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\StoreConfigure;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:mall:order-auto-complete')]
#[Description('商城订单超时自动完成任务')]
class OrderAutoCompleteCommand extends Command
{
    public function handle(OrderService $service): int
    {
        $this->info('开始执行订单自动完成扫描...');

        $configs = StoreConfigure::pluck('auto_complete_days', 'tenant_id');

        $count = 0;
        foreach ($configs as $tenantId => $days) {
            $count += $this->completeForTenant($service, (int) $tenantId, (int) $days);
        }

        // 处理没有特殊配置的租户（使用默认7天）
        $configuredTenantIds = $configs->keys()->all();
        $count += $this->completeForTenant($service, null, 7, $configuredTenantIds);

        $this->info("任务执行完毕，共自动完成 $count 笔订单。");

        return self::SUCCESS;
    }

    /**
     * 为指定租户批量完成超时订单
     */
    protected function completeForTenant(OrderService $service, ?int $tenantId, int $days, array $excludeTenantIds = []): int
    {
        $query = Order::where('status', OrderStatus::Signed)
            ->where('signed_at', '<=', now()->subDays($days));

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } elseif ($excludeTenantIds) {
            $query->whereNotIn('tenant_id', $excludeTenantIds);
        }

        $count = 0;
        $query->chunk(100, function (Collection $orders) use ($service, $days, &$count) {
            foreach ($orders as $order) {
                try {
                    $service->complete($order);
                    $count++;
                    $this->line("订单 [$order->no] 已自动完成（签收 $days 天后自动完成）");
                } catch (Throwable $e) {
                    $this->error("订单 [$order->no] 自动完成失败: ".$e->getMessage());
                }
            }
        });

        return $count;
    }
}
