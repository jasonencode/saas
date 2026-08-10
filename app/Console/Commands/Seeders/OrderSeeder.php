<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

use Random\RandomException;

#[Signature('seed:orders')]
class OrderSeeder extends Command
{
    public function handle(): void
    {
        $count = (int) text(
            label: '要生成的订单数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $user = User::find(1);
        $address = $user->addresses()->first();
        $tenantIds = [$tenantId];

        $orderService = service(OrderService::class);
        $total = 0;

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $skuItems = $this->buildSkuItems($tenantIds);
            if ($skuItems->isNotEmpty()) {
                $total += $this->createOrders($orderService, $user, $skuItems, $address);
            } else {
                $this->warn('未找到可下单的 SKU，跳过');
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("订单生成完成，共 {$total} 笔");
    }

    /**
     * 构造商品 SKU 订单明细
     *
     * @param  array<int, int>  $tenantIds
     *
     * @throws RandomException
     *
     * @return Collection<int, OrderItemDto>
     */
    private function buildSkuItems(array $tenantIds): Collection
    {
        $skus = Sku::whereHas('product', static fn ($q) => $q->whereIn('tenant_id', $tenantIds)->where('status', ProductStatus::Up))
            ->inRandomOrder()
            ->limit(random_int(4, 6))
            ->get();

        return $skus->map(fn ($sku) => OrderItemDto::make($sku, random_int(1, 3), fake('zh_CN')->sentence()));
    }

    /**
     * 调用下单服务并打印结果
     *
     * @param  Collection<int, OrderItemDto>  $items
     *
     * @throws \Throwable
     *
     * @return int 生成的订单数
     */
    private function createOrders(OrderService $orderService, User $user, Collection $items, mixed $address): int
    {
        $orders = $orderService->createOrders($user, $items->all(), $address);

        $this->info(sprintf('已生成 %d 笔订单', $orders->count()));

        foreach ($orders as $order) {
            $this->line(sprintf(
                '[%s] 租户 #%d 金额 ¥%s 明细 %d 项',
                $order->no,
                $order->tenant_id,
                $order->amount,
                $order->items->count(),
            ));
        }

        return $orders->count();
    }
}
