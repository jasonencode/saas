<?php

namespace Tests\Feature\User;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 身份退款按时长回退，而不是直接清空整段权益
 */
class IdentityRefundTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_renewal_refund_only_subtracts_the_refunded_days(): void
    {
        $identity = $this->makeIdentity(days: 30);
        $this->attach($identity, now()->addDays(60));

        [, $refundItem] = $this->makeRefund($identity, qty: 1, refundQty: 1);

        $identity->refund($refundItem, 1);

        $endAt = $this->pivotEndAt($identity);

        $this->assertNotNull($endAt);
        // 原有 30 天 + 续费 30 天，退掉续费后应剩 30 天，而不是整段清空
        $this->assertTrue($endAt->between(now()->addDays(29), now()->addDays(31)));
        $this->assertTrue($this->user->identities()->whereKey($identity->getKey())->exists());
    }

    public function test_partial_refund_subtracts_days_proportionally(): void
    {
        $identity = $this->makeIdentity(days: 30);
        $this->attach($identity, now()->addDays(60));

        [$orderItem, $firstRefundItem] = $this->makeRefund($identity, qty: 2, refundQty: 1);

        $identity->refund($firstRefundItem, 1);

        $this->assertTrue(
            $this->pivotEndAt($identity)->between(now()->addDays(29), now()->addDays(31))
        );

        $secondRefundItem = $this->makeRefundItem($orderItem, refundQty: 1);

        $identity->refund($secondRefundItem, 1);

        // 两次退完，剩余时长归零后撤销身份
        $this->assertNull($this->pivotEndAt($identity));
    }

    public function test_lifetime_identity_is_detached_only_after_all_qty_is_refunded(): void
    {
        $identity = $this->makeIdentity(days: 0);
        $this->attach($identity, null);

        [$orderItem, $firstRefundItem] = $this->makeRefund($identity, qty: 2, refundQty: 1);

        $identity->refund($firstRefundItem, 1);

        $this->assertTrue($this->user->identities()->whereKey($identity->getKey())->exists());

        $secondRefundItem = $this->makeRefundItem($orderItem, refundQty: 1);

        $identity->refund($secondRefundItem, 1);

        $this->assertFalse($this->user->identities()->whereKey($identity->getKey())->exists());
    }

    private function makeIdentity(int $days): Identity
    {
        return Identity::create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => '会员',
            'price' => 100,
            'days' => $days,
            'status' => true,
        ]);
    }

    private function attach(Identity $identity, ?Carbon $endAt): void
    {
        $this->user->identities()->attach($identity->getKey(), [
            'tenant_id' => $this->tenant->getKey(),
            'start_at' => now(),
            'end_at' => $endAt,
        ]);
    }

    /**
     * 创建订单项与一笔已完成的退款
     *
     * @return array{0: OrderItem, 1: RefundItem}
     */
    private function makeRefund(Identity $identity, int $qty, int $refundQty): array
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $this->user->getKey(),
            'amount' => 100,
            'freight' => 0,
            'coupon_discount' => 0,
            'status' => OrderStatus::Paid,
            'fulfillment_type' => FulfillmentType::Virtual,
        ]);

        $orderItem = $order->items()->create([
            'orderable_type' => $identity->getMorphClass(),
            'orderable_id' => $identity->getKey(),
            'orderable_name' => $identity->name,
            'qty' => $qty,
            'price' => 100,
        ]);

        return [$orderItem, $this->makeRefundItem($orderItem, $refundQty)];
    }

    private function makeRefundItem(OrderItem $orderItem, int $refundQty): RefundItem
    {
        $refund = Refund::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $this->user->getKey(),
            'order_id' => $orderItem->order_id,
            'total' => 100,
            'goods_amount' => 100,
            'freight_amount' => 0,
            'status' => RefundStatus::Completed,
            'type' => RefundType::OnlyRefund,
        ]);

        return $refund->items()->create([
            'order_item_id' => $orderItem->getKey(),
            'qty' => $refundQty,
            'price' => 100,
        ]);
    }

    private function pivotEndAt(Identity $identity): ?Carbon
    {
        $value = $this->user->identities()
            ->whereKey($identity->getKey())
            ->first()?->pivot?->end_at;

        return $value ? Carbon::parse($value) : null;
    }
}
