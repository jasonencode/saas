<?php

namespace App\Console\Commands\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\StoreConfigure;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
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
     *
     * mail 订单扫「已签收」（Signed），pickup 订单扫「已核销」（Verified），
     * virtual 订单支付即完成，无需自动完成。
     */
    protected function completeForTenant(OrderService $service, ?int $tenantId, int $days, array $excludeTenantIds = []): int
    {
        $count = 0;

        // mail：已签收 N 天后自动完成
        $mailQuery = Order::where('status', OrderStatus::Signed)
            ->where('fulfillment_type', FulfillmentType::Mail)
            ->where('signed_at', '<=', now()->subDays($days));

        // pickup：已核销 N 天后自动完成
        $pickupQuery = Order::where('status', OrderStatus::Verified)
            ->where('fulfillment_type', FulfillmentType::Pickup)
            ->where('verified_at', '<=', now()->subDays($days));

        $query = $mailQuery->union($pickupQuery);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } elseif ($excludeTenantIds) {
            $query->whereNotIn('tenant_id', $excludeTenantIds);
        }

        $query->chunk(100, function (Collection $orders) use ($service, $days, &$count) {
            foreach ($orders as $order) {
                try {
                    $service->complete($order);
                    $count++;
                    $this->line(sprintf('订单 [%s] 已自动完成（%s %d 天后自动完成）', $order->no, $order->fulfillment_type === FulfillmentType::Pickup ? '核销' : '签收', $days));
                } catch (Throwable $e) {
                    $this->error("订单 [$order->no] 自动完成失败: ".$e->getMessage());
                }
            }
        });

        return $count;
    }
}
