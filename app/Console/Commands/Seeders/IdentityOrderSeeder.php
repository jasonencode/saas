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

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

use Random\RandomException;

#[Signature('seed:identity-orders')]
class IdentityOrderSeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );
        $count = (int) text(
            label: '要生成的订单数量',
            default: '1',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $user = User::find(1);
        $address = $user->addresses()->first();
        $orderService = service(OrderService::class);
        $total = 0;

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $identityItems = $this->buildIdentityItems($tenantId);
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
     * @param  int  $tenantId  租户 ID
     *
     * @throws RandomException
     *
     * @return Collection<int, OrderItemDto>
     */
    private function buildIdentityItems(int $tenantId): Collection
    {
        $identities = Identity::where('can_subscribe', true)
            ->where('status', true)
            ->where('tenant_id', $tenantId)
            ->inRandomOrder()
            ->limit(random_int(1, 2))
            ->get();

        return $identities->map(fn ($identity) => OrderItemDto::make($identity, random_int(1, 3), fake('zh_CN')->sentence()));
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
