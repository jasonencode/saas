<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\FulfillmentType;
use App\Events\Mall\OrderCreated;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Delivery;
use App\Models\Mall\Order;
use App\Models\Mall\OrderAddress;
use App\Models\Mall\PickupPoint;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\Address;
use App\Services\Campaign\CouponService;
use App\Services\Mall\DeliveryService;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\System\EventLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 创建单租户订单（订单主记录 + 明细 + 收货信息 + 库存/优惠券核销）
 *
 * 入口：OrderService::createOrder() / CreateOrder::dispatch()；
 * 跨租户拆单由 Create 动作按租户循环调用本动作。
 *
 * before：商品项、履约方式、自提点与收货地址校验
 * handle：事务内算价、落库、扣库存、核销券、写地址快照
 * log   ：写日志中心 order.created
 * after ：空（OrderCreated 事件在 handle 内派发，保证监听器在事务内可见订单）
 */
final class CreateOrder extends AbstractOperation
{
    /**
     * 已校验的收货地址（before 解析后供 handle 复用）
     */
    private ?Address $resolvedAddress = null;

    /**
     * @param  Tenant  $tenant  所属租户
     * @param  Authenticatable  $user  下单用户
     * @param  OrderItemDto[]  $items  商品项
     * @param  FulfillmentType  $fulfillmentType  履约方式
     * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
     * @param  string|null  $remark  订单备注
     * @param  int|null  $pickupPointId  自提点 ID
     * @param  int|null  $couponUserId  用户持券实例 ID
     */
    public function __construct(
        private readonly Tenant $tenant,
        private readonly Authenticatable $user,
        private readonly array $items,
        private readonly FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        private readonly Address|int|null $address = null,
        private readonly ?string $remark = null,
        private readonly ?int $pickupPointId = null,
        private readonly ?int $couponUserId = null,
    ) {}

    /**
     * 前置：商品项格式、自提点归属与收货地址归属
     */
    public function before(): void
    {
        if ($this->items === []) {
            throw new InvalidArgumentException('订单无商品');
        }

        foreach ($this->items as $item) {
            if (!($item instanceof OrderItemDto)) {
                throw new InvalidArgumentException('选择的商品必须实现 OrderItemDto 类');
            }

            // 校验订单项是否支持所选履约方式，任一不支持则整单拒绝
            if (!$item->orderable->supportsFulfillmentType($this->fulfillmentType)) {
                throw new RuntimeException(sprintf(
                    '商品[%s]不支持[%s]履约方式',
                    $item->orderable->getOrderableName(),
                    $this->fulfillmentType->getLabel()
                ));
            }
        }

        // 门店自提：校验自提点存在且启用、归属当前租户
        if ($this->fulfillmentType === FulfillmentType::Pickup) {
            $pickupPoint = $this->pickupPointId ? PickupPoint::find($this->pickupPointId) : null;

            if (!$pickupPoint || !$pickupPoint->status || $pickupPoint->tenant_id !== $this->tenant->id) {
                throw new RuntimeException('自提点不正确');
            }
        }

        $this->resolveAddress();
    }

    /**
     * 事务内创建订单并完成资源占用
     *
     * @throws Throwable
     */
    public function handle(): Order
    {
        $items = collect($this->items);
        $address = $this->resolvedAddress ?? $this->resolveAddress();

        return DB::transaction(function () use ($items, $address): Order {
            $amount = $items->reduce(
                fn (string $total, OrderItemDto $item): string => bcadd($total, $item->getAmount(), 2),
                '0.00'
            );

            $freight = $this->calculateOrderFreight($items, $address);

            $order = Order::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->getKey(),
                'amount' => $amount,
                'freight' => $freight,
                'fulfillment_type' => $this->fulfillmentType,
                'pickup_point_id' => $this->fulfillmentType === FulfillmentType::Pickup ? $this->pickupPointId : null,
                'remark' => $this->remark,
            ]);

            foreach ($items as $item) {
                $orderable = $item->orderable;

                $order->items()->create([
                    'orderable_type' => $orderable::class,
                    'orderable_id' => $orderable->getKey(),
                    'orderable_name' => $orderable->getOrderableName(),
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'remark' => $item->remark,
                ]);

                // 库存扣减委托给可订购主体
                if ($orderable->getDeductStockType() === DeductStockType::Ordered) {
                    $orderable->deductStock($item->qty);
                }
            }

            // 下单即核销（占用），失败抛异常回滚整单
            if ($this->couponUserId) {
                $couponUser = CouponUser::findOrFail($this->couponUserId);

                service(CouponService::class)->applyToOrder($couponUser, $order, $items);
            }

            if ($address) {
                $orderAddress = new OrderAddress;
                $orderAddress->fillFromAddress($address);
                $order->address()->save($orderAddress);
            }

            // 下单即建立用户与租户的关联，便于租户后台统计其用户量
            $this->user->tenants()->syncWithoutDetaching($this->tenant->getKey());

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    /**
     * 记录订单创建日志
     *
     * @param  mixed  $result  handle() 返回的订单
     */
    public function log(mixed $result): void
    {
        if (!$result instanceof Order) {
            return;
        }

        service(EventLogService::class)->write('order.created', '创建订单', $this->user, $result, null, [
            'items_count' => count($this->items),
            'amount' => $result->total_amount,
            'coupon_user_id' => $this->couponUserId,
        ]);
    }

    /**
     * 解析并校验收货地址归属
     *
     * 仅允许使用下单用户本人的收货地址，防止越权绑定他人地址。
     */
    private function resolveAddress(): ?Address
    {
        if ($this->address instanceof Address) {
            $address = $this->address;
        } elseif (is_numeric($this->address) && (int) $this->address > 0) {
            $address = Address::whereKey($this->address)
                ->where('user_id', $this->user->getKey())
                ->first();

            if (!$address) {
                throw new RuntimeException('地址不正确');
            }
        } else {
            return null;
        }

        // 直接传入地址对象时同样校验归属，避免绕过 FormRequest 越权使用
        if ((int) $address->user_id !== (int) $this->user->getKey()) {
            throw new RuntimeException('地址不正确');
        }

        return $this->resolvedAddress = $address;
    }

    /**
     * 计算订单运费
     *
     * 仅快递邮寄（mail）履约方式按运费模板计费，门店自提与虚拟商品免运费。
     *
     * @param  Collection<int, OrderItemDto>  $items  订单商品列表
     * @param  Address|null  $address  收货地址
     */
    private function calculateOrderFreight(Collection $items, ?Address $address): string
    {
        // 非快递邮寄履约方式免运费
        if ($this->fulfillmentType !== FulfillmentType::Mail) {
            return '0.00';
        }

        $deliveryService = service(DeliveryService::class);

        $provinceId = $address?->province_id ?? null;
        $cityId = $address?->city_id ?? null;
        $districtId = $address?->district_id ?? null;

        $totalFreight = '0.00';

        // 仅 Sku（实体商品）参与运费计算，其他 Orderable 类型免运费
        $deliverableItems = $items->filter(fn (OrderItemDto $item): bool => $item->orderable instanceof Sku);
        if ($deliverableItems->isEmpty()) {
            return $totalFreight;
        }

        $groupedByDelivery = $deliverableItems->groupBy(
            fn (OrderItemDto $item) => $item->orderable->product->delivery_id ?? 'default'
        );

        foreach ($groupedByDelivery as $deliveryId => $groupItems) {
            $delivery = $deliveryId === 'default'
                ? $deliveryService->getDefaultForTenant($this->tenant->id)
                : Delivery::find($deliveryId);

            if ($delivery) {
                $freight = $deliveryService->calculateOrderFreight(
                    delivery: $delivery,
                    items: $groupItems,
                    provinceId: $provinceId,
                    cityId: $cityId,
                    districtId: $districtId,
                );
                $totalFreight = bcadd($totalFreight, $freight, 2);
            }
        }

        return $totalFreight;
    }
}
