<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\ExpiredType;
use App\Enums\Mall\FulfillmentType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Services\Campaign\CouponService;
use App\Services\Mall\DTOs\OrderItemDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CouponService::class);
    }

    // ========================================
    // 测试夹具
    // ========================================

    /**
     * 创建租户
     */
    private function makeTenant(): Tenant
    {
        return Tenant::factory()->create();
    }

    /**
     * 创建实体商品 SKU（所属租户由商品决定）
     *
     * @param  Tenant  $tenant  所属租户
     * @param  string  $price  单价
     */
    private function makeSku(Tenant $tenant, string $price = '100.00'): Sku
    {
        $product = Product::factory()->for($tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);

        return Sku::factory()->create([
            'product_id' => $product->id,
            'price' => $price,
            'stock' => 100,
        ]);
    }

    /**
     * 创建可订购身份（非 Sku 订单项）
     */
    private function makeIdentity(Tenant $tenant): Identity
    {
        return Identity::create([
            'tenant_id' => $tenant->id,
            'name' => '会员身份',
            'price' => '100.00',
            'can_subscribe' => true,
            'status' => true,
        ]);
    }

    /**
     * 创建订单
     */
    private function makeOrder(Tenant $tenant, User $user, string $amount = '100.00'): Order
    {
        return Order::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->getKey(),
            'amount' => $amount,
            'freight' => '0.00',
            'fulfillment_type' => FulfillmentType::Mail,
        ]);
    }

    /**
     * 创建用户持券实例
     */
    private function makeCouponUser(Coupon $coupon, User $user, ?Carbon $expiredAt = null): CouponUser
    {
        return CouponUser::query()->create([
            'coupon_id' => $coupon->getKey(),
            'user_id' => $user->getKey(),
            'expired_at' => $expiredAt ?? now()->addDay(),
        ]);
    }

    // ========================================
    // calculateDiscount - 固定金额优惠券
    // ========================================

    public function test_fixed_coupon_returns_discount_amount(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['value' => 10.00]);

        $discount = $this->service->calculateDiscount($coupon, 50.00);

        $this->assertEquals(10.00, $discount);
    }

    public function test_fixed_coupon_discount_not_exceed_total_amount(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['value' => 100.00]);

        $discount = $this->service->calculateDiscount($coupon, 50.00);

        $this->assertEquals(50.00, $discount);
    }

    // ========================================
    // calculateDiscount - 百分比优惠券
    // ========================================

    public function test_percent_coupon_returns_discount_amount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 80,
            'max_discount' => null,
        ]);

        $discount = $this->service->calculateDiscount($coupon, 100.00);

        $this->assertEquals(80.00, $discount);
    }

    public function test_percent_coupon_respects_max_discount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 80,
            'max_discount' => 50.00,
        ]);

        $discount = $this->service->calculateDiscount($coupon, 100.00);

        // 100 * 80% = 80, 但 max_discount = 50
        $this->assertEquals(50.00, $discount);
    }

    public function test_percent_coupon_below_max_discount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 20,
            'max_discount' => 50.00,
        ]);

        $discount = $this->service->calculateDiscount($coupon, 100.00);

        // 100 * 20% = 20, 低于 max_discount
        $this->assertEquals(20.00, $discount);
    }

    // ========================================
    // calculateDiscount - 最低消费
    // ========================================

    public function test_coupon_throws_when_min_amount_not_met(): void
    {
        $coupon = Coupon::factory()->fixed()
            ->withMinAmount(100.00)
            ->create(['value' => 10.00]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/最低需要/');

        $this->service->calculateDiscount($coupon, 50.00);
    }

    public function test_coupon_passes_when_min_amount_met(): void
    {
        $coupon = Coupon::factory()->fixed()
            ->withMinAmount(100.00)
            ->create(['value' => 10.00]);

        $discount = $this->service->calculateDiscount($coupon, 150.00);

        $this->assertEquals(10.00, $discount);
    }

    // ========================================
    // calculateDiscount - 无效/过期优惠券
    // ========================================

    public function test_expired_coupon_throws_exception(): void
    {
        $coupon = Coupon::factory()->expired()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->calculateDiscount($coupon, 100.00);
    }

    public function test_disabled_coupon_throws_exception(): void
    {
        $coupon = Coupon::factory()->disabled()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->calculateDiscount($coupon, 100.00);
    }

    // ========================================
    // sendToUser - 正常发放
    // ========================================

    public function test_send_coupon_to_user_success(): void
    {
        $coupon = Coupon::factory()->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_send_multiple_coupons_to_user(): void
    {
        $coupon = Coupon::factory()->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 3);

        $this->assertEquals(3, $coupon->users()->where('user_id', $user->id)->count());
    }

    public function test_send_coupon_sets_expired_at_for_fixed_type(): void
    {
        $coupon = Coupon::factory()->create([
            'expired_type' => ExpiredType::Fixed,
            'end_at' => now()->addDays(30),
        ]);
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $couponUser = $coupon->users()->where('user_id', $user->id)->first();
        $this->assertNotNull($couponUser->pivot->expired_at);
        $this->assertEquals($coupon->end_at->format('Y-m-d H:i:s'), $couponUser->pivot->expired_at->format('Y-m-d H:i:s'));
    }

    public function test_send_coupon_sets_expired_at_for_receive_type(): void
    {
        $coupon = Coupon::factory()->create([
            'expired_type' => ExpiredType::Receive,
            'days' => 7,
        ]);
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $couponUser = $coupon->users()->where('user_id', $user->id)->first();
        $this->assertNotNull($couponUser->pivot->expired_at);
        $this->assertTrue($couponUser->pivot->expired_at->isAfter(now()->addDays(6)));
    }

    // ========================================
    // sendToUser - 发放上限
    // ========================================

    public function test_send_coupon_fails_when_usage_limit_reached(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimit(2)
            ->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->service->sendToUser($coupon, $user1, 1);
        $this->service->sendToUser($coupon, $user2, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券发放已达上限');

        $this->service->sendToUser($coupon, $user3, 1);
    }

    public function test_send_coupon_fails_when_qty_exceeds_remaining(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimit(3)
            ->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/剩余可发放数量不足/');

        $this->service->sendToUser($coupon, $user, 5);
    }

    // ========================================
    // sendToUser - 每人限领
    // ========================================

    public function test_send_coupon_fails_when_per_user_limit_reached(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimitPerUser(1)
            ->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/不可重复领取/');

        $this->service->sendToUser($coupon, $user, 1);
    }

    public function test_send_coupon_fails_when_qty_exceeds_per_user_remaining(): void
    {
        $coupon = Coupon::factory()
            ->withUsageLimitPerUser(3)
            ->create();
        $user = User::factory()->create();

        $this->service->sendToUser($coupon, $user, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/最多还可领取/');

        $this->service->sendToUser($coupon, $user, 3);
    }

    public function test_send_coupon_throws_when_lock_is_held(): void
    {
        $coupon = Coupon::factory()->create();
        $user = User::factory()->create();

        // 持有发放锁，验证并发领取时阻塞超时转为业务异常
        $lock = Cache::lock("coupon_send_{$coupon->getKey()}", 10);
        $this->assertTrue($lock->get());

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('当前领取人数较多，请稍后再试');

            $this->service->sendToUser($coupon, $user, 1);
        } finally {
            $lock->release();
        }
    }

    // ========================================
    // calculateDiscount - bcmath 边界
    // ========================================

    public function test_fixed_discount_capped_by_base_amount(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['value' => 10.00]);

        // 面额 10 > 基数 5，抵扣取基数
        $this->assertSame('5.00', $this->service->calculateDiscount($coupon, '5.00'));
    }

    public function test_percent_discount_rounds_half_up_to_cents(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 15,
            'max_discount' => null,
        ]);

        // 33.33 × 15% = 4.9995，四舍五入为 5.00（而非 bcdiv 截断的 4.99）
        $this->assertSame('5.00', $this->service->calculateDiscount($coupon, '33.33'));
    }

    public function test_percent_discount_uncapped_without_max_discount(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'value' => 80,
            'max_discount' => null,
        ]);

        $this->assertSame('80.00', $this->service->calculateDiscount($coupon, '100.00'));
    }

    public function test_min_amount_boundary_equal_passes(): void
    {
        $coupon = Coupon::factory()->fixed()->withMinAmount(100.00)->create(['value' => 10.00]);

        $this->assertSame('10.00', $this->service->calculateDiscount($coupon, '100.00'));
    }

    public function test_min_amount_boundary_short_by_one_cent_throws(): void
    {
        $coupon = Coupon::factory()->fixed()->withMinAmount(100.00)->create(['value' => 10.00]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/最低需要/');

        $this->service->calculateDiscount($coupon, '99.99');
    }

    // ========================================
    // baseAmountFor - 适用商品范围
    // ========================================

    public function test_base_amount_for_covers_all_items_when_no_product_scope(): void
    {
        $tenant = $this->makeTenant();
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);

        $items = collect([
            OrderItemDto::make($this->makeSku($tenant, '60.00'), 1),
            OrderItemDto::make($this->makeSku($tenant, '40.00'), 1),
        ]);

        $this->assertSame('100.00', $this->service->baseAmountFor($coupon, $items));
    }

    public function test_base_amount_for_counts_matched_products_only(): void
    {
        $tenant = $this->makeTenant();
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);

        $matchedSku = $this->makeSku($tenant, '60.00');
        $otherSku = $this->makeSku($tenant, '40.00');
        $coupon->products()->attach($matchedSku->product_id);

        $items = collect([
            OrderItemDto::make($matchedSku, 1),
            OrderItemDto::make($otherSku, 1),
        ]);

        $this->assertSame('60.00', $this->service->baseAmountFor($coupon, $items));
    }

    public function test_base_amount_for_returns_null_when_no_product_matched(): void
    {
        $tenant = $this->makeTenant();
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);
        $coupon->products()->attach($this->makeSku($tenant, '60.00')->product_id);

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '40.00'), 1)]);

        $this->assertNull($this->service->baseAmountFor($coupon, $items));
    }

    public function test_base_amount_for_excludes_non_sku_items(): void
    {
        $tenant = $this->makeTenant();
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);

        // 全场券 + 仅非 Sku 订单项：不参与抵扣基数
        $items = collect([OrderItemDto::make($this->makeIdentity($tenant), 1)]);

        $this->assertSame('0.00', $this->service->baseAmountFor($coupon, $items));
    }

    // ========================================
    // 最低实付 clamp（预览与核销同一路径）
    // ========================================

    public function test_preview_clamps_discount_to_keep_min_payable(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id, 'value' => 100.00]);
        $couponUser = $this->makeCouponUser($coupon, $user);

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '60.00'), 1)]);

        $result = $this->service->previewDiscount($couponUser, $user, $items);

        // 商品实付最低 0.01：抵扣 clamp 至 60.00 - 0.01
        $this->assertSame('59.99', $result['discount']);
        $this->assertSame('60.00', $result['base_amount']);
    }

    public function test_preview_clamps_percent_discount_to_keep_min_payable(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->percent()->create([
            'tenant_id' => $tenant->id,
            'value' => 100,
            'max_discount' => null,
        ]);
        $couponUser = $this->makeCouponUser($coupon, $user);

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '80.00'), 1)]);

        $this->assertSame('79.99', $this->service->previewDiscount($couponUser, $user, $items)['discount']);
    }

    public function test_preview_and_apply_produce_same_discount(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->percent()->create([
            'tenant_id' => $tenant->id,
            'value' => 30,
            'max_discount' => null,
        ]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '99.90');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '99.90'), 1)]);

        $previewed = $this->service->previewDiscount($couponUser, $user, $items)['discount'];
        $applied = $this->service->applyToOrder($couponUser, $order, $items);

        $this->assertSame($previewed, $applied);
        $this->assertSame('29.97', $applied);
    }

    // ========================================
    // applyToOrder - 核销
    // ========================================

    public function test_apply_to_order_writes_discount_and_marks_coupon_used(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id, 'value' => 20.00]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $discount = $this->service->applyToOrder($couponUser, $order, $items);

        $this->assertSame('20.00', $discount);

        // 持券实例已核销
        $couponUser->refresh();
        $this->assertTrue((bool) $couponUser->is_used);
        $this->assertNotNull($couponUser->used_at);

        // 订单抵扣与实付口径
        $this->assertSame('20.00', $order->fresh()->coupon_discount);
        $this->assertSame(80.0, $order->fresh()->total_amount);

        // 用券记录落库
        $this->assertDatabaseHas('coupon_order', [
            'order_id' => $order->getKey(),
            'coupon_id' => $coupon->getKey(),
            'coupon_user_id' => $couponUser->getKey(),
            'discount_amount' => 20.00,
        ]);
    }

    public function test_apply_to_order_throws_for_coupon_of_other_user(): void
    {
        $tenant = $this->makeTenant();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id]);
        $couponUser = $this->makeCouponUser($coupon, $owner);
        $order = $this->makeOrder($tenant, $other, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券不属于当前用户');

        $this->service->applyToOrder($couponUser, $order, $items);
    }

    public function test_apply_to_order_throws_for_used_coupon(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $couponUser->update(['is_used' => true, 'used_at' => now()]);
        $order = $this->makeOrder($tenant, $user, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券已被使用');

        $this->service->applyToOrder($couponUser, $order, $items);
    }

    public function test_apply_to_order_throws_for_expired_instance(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id]);
        $couponUser = $this->makeCouponUser($coupon, $user, now()->subDay());
        $order = $this->makeOrder($tenant, $user, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券已过期');

        $this->service->applyToOrder($couponUser, $order, $items);
    }

    public function test_apply_to_order_throws_for_disabled_coupon(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->disabled()->create(['tenant_id' => $tenant->id]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券已失效');

        $this->service->applyToOrder($couponUser, $order, $items);
    }

    public function test_apply_to_order_throws_when_tenant_mismatched(): void
    {
        $tenant = $this->makeTenant();
        $otherTenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $otherTenant->id]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券不适用于所选商品');

        $this->service->applyToOrder($couponUser, $order, $items);
    }

    public function test_apply_to_order_throws_when_no_product_matched(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id]);
        $coupon->products()->attach($this->makeSku($tenant, '50.00')->product_id);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '100.00');

        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('优惠券不适用于所选商品');

        $this->service->applyToOrder($couponUser, $order, $items);
    }

    // ========================================
    // releaseFromOrder - 释放
    // ========================================

    public function test_release_from_order_restores_coupon(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id, 'value' => 20.00]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '100.00');
        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->service->applyToOrder($couponUser, $order, $items);
        $this->service->releaseFromOrder($order);

        $couponUser->refresh();
        $this->assertFalse((bool) $couponUser->is_used);
        $this->assertNull($couponUser->used_at);
        $this->assertSame(0.0, (float) $order->fresh()->coupon_discount);
        $this->assertDatabaseMissing('coupon_order', ['order_id' => $order->getKey()]);
    }

    public function test_release_from_order_without_record_is_noop(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $order = $this->makeOrder($tenant, $user, '100.00');

        $this->service->releaseFromOrder($order);

        $this->assertSame(0.0, (float) $order->fresh()->coupon_discount);
        $this->assertDatabaseCount('coupon_order', 0);
    }

    public function test_release_from_order_restores_expired_coupon(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $tenant->id, 'value' => 20.00]);
        $couponUser = $this->makeCouponUser($coupon, $user);
        $order = $this->makeOrder($tenant, $user, '100.00');
        $items = collect([OrderItemDto::make($this->makeSku($tenant, '100.00'), 1)]);

        $this->service->applyToOrder($couponUser, $order, $items);

        // 释放时券实例已过期：仍恢复未使用（过期态由 expired_at 表达）
        $couponUser->update(['expired_at' => now()->subDay()]);
        $this->service->releaseFromOrder($order);

        $this->assertFalse((bool) $couponUser->fresh()->is_used);
    }

    // ========================================
    // 过期券定时清理
    // ========================================

    public function test_expire_command_deletes_only_expired_unused_coupons(): void
    {
        $coupon = Coupon::factory()->create();
        $expired = $this->makeCouponUser($coupon, User::factory()->create(), now()->subDay());
        $used = $this->makeCouponUser($coupon, User::factory()->create(), now()->subDay());
        $used->update(['is_used' => true, 'used_at' => now()]);
        $active = $this->makeCouponUser($coupon, User::factory()->create(), now()->addDay());

        $this->artisan('app:campaign:coupon-expire')->assertSuccessful();

        $this->assertDatabaseMissing('coupon_user', ['id' => $expired->getKey()]);
        $this->assertDatabaseHas('coupon_user', ['id' => $used->getKey()]);
        $this->assertDatabaseHas('coupon_user', ['id' => $active->getKey()]);
    }

    public function test_expire_command_is_idempotent(): void
    {
        $coupon = Coupon::factory()->create();
        $this->makeCouponUser($coupon, User::factory()->create(), now()->subDay());

        $this->artisan('app:campaign:coupon-expire')->assertSuccessful();
        $this->artisan('app:campaign:coupon-expire')->assertSuccessful();

        $this->assertDatabaseCount('coupon_user', 0);
    }

    public function test_expire_command_releases_per_user_quota(): void
    {
        $coupon = Coupon::factory()->withUsageLimitPerUser(1)->create();
        $user = User::factory()->create();
        $this->makeCouponUser($coupon, $user, now()->subDay());

        $this->artisan('app:campaign:coupon-expire')->assertSuccessful();

        // 过期券清理后额度释放，可重新领取
        $this->service->sendToUser($coupon, $user, 1);

        $this->assertSame(1, $coupon->users()->where('user_id', $user->getKey())->count());
    }
}
