<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\PickupPoint;
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
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );
        $count = (int) text(
            label: '要生成的订单数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
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
        $this->info("订单生成完成，共 $total 笔");
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
     * 履约方式从所有商品支持的履约类型并集中随机选取（排除虚拟商品），
     * 仅将支持所选履约类型的商品纳入订单，保证整单履约类型一致。
     *
     * @param  Collection<int, OrderItemDto>  $items
     *
     * @throws \Throwable
     *
     * @return int 生成的订单数
     */
    private function createOrders(OrderService $orderService, User $user, Collection $items, mixed $address): int
    {
        $fulfillmentType = $this->randomFulfillmentType($items);

        if ($fulfillmentType === null) {
            $this->warn('所选商品均未设置履约类型，跳过该批订单');

            return 0;
        }

        // 仅保留支持所选履约类型的商品（整单履约方式一致）
        $orderItems = $items->filter(
            fn (OrderItemDto $item) => $item->orderable->supportsFulfillmentType($fulfillmentType)
        );

        if ($orderItems->isEmpty()) {
            $this->warn('没有商品支持所选履约方式，跳过该批订单');

            return 0;
        }

        // 门店自提订单需指定自提点：取当前租户启用的自提点，未配置则跳过该批
        $pickupPointId = null;
        if ($fulfillmentType === FulfillmentType::Pickup) {
            $pickupPointId = PickupPoint::where('tenant_id', $orderItems->first()->tenantId)
                ->where('status', true)
                ->value('id');

            if ($pickupPointId === null) {
                $this->warn('该租户未配置启用的自提点，跳过该批订单');

                return 0;
            }
        }

        $orders = $orderService->createOrders(
            user: $user,
            items: $orderItems->values()->all(),
            fulfillmentType: $fulfillmentType,
            address: $address,
            pickupPointId: $pickupPointId
        );

        $this->info(sprintf('已生成 %d 笔订单（履约方式：%s）', $orders->count(), $fulfillmentType->getLabel()));

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

    /**
     * 从所有商品支持的履约类型并集中随机选取
     *
     * 仅从快递邮寄（mail）/门店自提（pickup）中随机，虚拟商品（virtual）不参与。
     * 返回 null 表示所有商品均未设置履约类型。
     *
     * @param  Collection<int, OrderItemDto>  $items
     */
    private function randomFulfillmentType(Collection $items): ?FulfillmentType
    {
        $availableTypes = [];

        foreach ($items as $item) {
            foreach (FulfillmentType::cases() as $type) {
                if ($type !== FulfillmentType::Virtual && $item->orderable->supportsFulfillmentType($type)) {
                    $availableTypes[$type->value] = $type;
                }
            }
        }

        return $availableTypes === [] ? null : $availableTypes[array_rand($availableTypes)];
    }
}
