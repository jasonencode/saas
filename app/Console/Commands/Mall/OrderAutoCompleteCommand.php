<?php

namespace App\Console\Commands\Mall;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\StoreConfigure;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

#[Signature('app:mall:order-auto-complete')]
#[Description('商城订单超时自动完成任务')]
#[CommandLabel('订单自动完成')]
class OrderAutoCompleteCommand extends BaseCommand
{
    /**
     * 本次执行中逐条失败的数量
     *
     * 命令内部已捕获逐条异常，进程仍正常退出，失败明细只能经 logContext() 上报。
     */
    protected int $failed = 0;

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

        $this->logContext(['completed' => $count, 'failed' => $this->failed]);

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

        // 在每个子查询上分别应用租户条件
        if ($tenantId) {
            $mailQuery->where('tenant_id', $tenantId);
            $pickupQuery->where('tenant_id', $tenantId);
        } elseif ($excludeTenantIds) {
            $mailQuery->whereNotIn('tenant_id', $excludeTenantIds);
            $pickupQuery->whereNotIn('tenant_id', $excludeTenantIds);
        }

        // 分别处理两种订单，避免 UNION + chunk 的 ORDER BY 兼容问题
        $this->processOrders($mailQuery, $service, $days, '签收', $count);
        $this->processOrders($pickupQuery, $service, $days, '核销', $count);

        return $count;
    }

    /**
     * 批量处理订单
     */
    private function processOrders($query, OrderService $service, int $days, string $label, int &$count): void
    {
        $query->chunk(100, function (Collection $orders) use ($service, $days, $label, &$count) {
            foreach ($orders as $order) {
                try {
                    $service->complete($order, $this->user());
                    $count++;
                    $this->line(sprintf('订单 [%s] 已自动完成（%s %d 天后自动完成）', $order->no, $label, $days));
                } catch (Throwable $e) {
                    $this->failed++;
                    $this->error("订单 [$order->no] 自动完成失败: ".$e->getMessage());
                }
            }
        });
    }
}
