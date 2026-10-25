<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\FulfillmentType;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use App\Models\User\Address;
use App\Services\Mall\DTOs\OrderItemDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 跨租户创建订单（按租户拆单）
 *
 * 入口：OrderService::createOrders() / Create::dispatch()。
 *
 * before：商品项与优惠券租户校验
 * handle：外层事务内按租户分组，逐组调用 CreateOrder 落库，任一失败整体回滚
 * log   ：空（每个子订单由 CreateOrder 各自写 order.created）
 * after ：空
 */
final class Create extends AbstractOperation
{
    /**
     * 已解析的用户持券实例（before 校验后供 handle 复用）
     */
    private ?CouponUser $couponUser = null;

    /**
     * @param  Authenticatable  $user  下单用户
     * @param  OrderItemDto[]  $items  商品项
     * @param  FulfillmentType  $fulfillmentType  履约方式
     * @param  Address|int|null  $address  收货地址
     * @param  int|null  $pickupPointId  自提点 ID
     * @param  int|null  $couponUserId  用户持券实例 ID
     */
    public function __construct(
        private readonly Authenticatable $user,
        private readonly array $items,
        private readonly FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        private readonly Address|int|null $address = null,
        private readonly ?int $pickupPointId = null,
        private readonly ?int $couponUserId = null,
    ) {}

    /**
     * 前置：商品项非空且格式正确，优惠券必须命中本次拆单的租户集合
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
        }

        if (!$this->couponUserId) {
            return;
        }

        $this->couponUser = CouponUser::findOrFail($this->couponUserId);
        $tenantIds = collect($this->items)->pluck('tenantId')->map(static fn ($id): int => (int) $id);

        // 券定义被软删时 coupon 为 null，与租户不匹配同样拒绝整单
        if (!$this->couponUser->coupon || !$tenantIds->contains((int) $this->couponUser->coupon->tenant_id)) {
            throw new InvalidArgumentException('优惠券不适用于所选商品');
        }
    }

    /**
     * 按租户拆单并在同一事务内创建
     *
     * @throws Throwable
     *
     * @return Collection<int, Order>
     */
    public function handle(): Collection
    {
        $grouped = collect($this->items)->groupBy(fn (OrderItemDto $item): int => $item->tenantId);

        // 跨租户整体原子：任一租户建单失败则全部回滚，避免部分订单落库后
        // 用户重试造成重复下单与库存二次扣减（CreateOrder 内部事务为嵌套 savepoint）
        return DB::transaction(function () use ($grouped): Collection {
            $orders = collect();

            foreach ($grouped as $tenantId => $tenantItems) {
                $tenant = Tenant::find($tenantId);

                if (!$tenant) {
                    throw new RuntimeException("租户不存在: $tenantId");
                }

                // 券仅透传给其所属租户的子订单（before 已确认 coupon 非空）
                $orderCouponUserId = $this->couponUser && (int) $this->couponUser->coupon->tenant_id === (int) $tenantId
                    ? $this->couponUser->getKey()
                    : null;

                $orders->push(CreateOrder::dispatch(
                    tenant: $tenant,
                    user: $this->user,
                    items: $tenantItems->all(),
                    fulfillmentType: $this->fulfillmentType,
                    address: $this->address,
                    pickupPointId: $this->pickupPointId,
                    couponUserId: $orderCouponUserId,
                ));
            }

            return $orders;
        });
    }
}
