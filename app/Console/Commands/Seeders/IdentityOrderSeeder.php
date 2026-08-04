<?php

namespace App\Console\Commands\Seeders;

use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use Random\RandomException;

use function Laravel\Prompts\text;

#[Signature('seed:identity-orders')]
class IdentityOrderSeeder extends Command
{
    public function handle(): void
    {
        $count = (int) text(
            label: '要生成的订单数量',
            default: '1',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $user = User::find(1);
        $address = $user->addresses()->first();
        $tenantIds = Tenant::pluck('id')->all();

        if (empty($tenantIds)) {
            $this->error('未找到任何租户');

            return;
        }

        $orderService = service(OrderService::class);
        $total = 0;

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $identityItems = $this->buildIdentityItems($tenantIds);
            if ($identityItems->isNotEmpty()) {
                $total += $this->createOrders($orderService, $user, $identityItems, $address);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("身份订单生成完成，共 {$total} 笔");
    }

    /**
     * 构造身份订阅订单明细
     *
     * @param  array<int, int>  $tenantIds
     *
     * @return Collection<int, OrderItemDto>
     * @throws RandomException
     */
    private function buildIdentityItems(array $tenantIds): Collection
    {
        $identities = Identity::where('can_subscribe', true)
            ->where('status', true)
            ->whereIn('tenant_id', $tenantIds)
            ->inRandomOrder()
            ->limit(random_int(1, 2))
            ->get();

        return $identities->map(fn ($identity) => OrderItemDto::make($identity, random_int(1, 3), fake('zh_CN')->sentence()));
    }

    /**
     * 调用下单服务并打印结果
     *
     * @param  OrderService  $orderService
     * @param  User  $user
     * @param  Collection<int, OrderItemDto>  $items
     * @param  mixed  $address
     *
     * @return int 生成的订单数
     *
     * @throws \Throwable
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
