<?php

namespace Tests\Feature\Mall;

use App\Enums\Campaign\CouponType;
use App\Enums\Mall\FulfillmentType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Cart;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 优惠券核销链路（结算预览 / 下单抵扣 / 可用券接口）
 */
class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Product $product;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        // 券接口按「用户授权租户」过滤，故先建立授权关系
        $this->user->tenants()->attach($this->tenant);

        [$this->product, $this->sku] = $this->makeProductSku($this->tenant, '100.00');
    }

    // ========================================
    // 测试夹具
    // ========================================

    /**
     * 创建虚拟商品与其 SKU（免运费，便于金额断言）
     *
     * @return array{Product, Sku} 商品与 SKU
     */
    private function makeProductSku(Tenant $tenant, string $price, int $stock = 100): array
    {
        $product = Product::factory()->for($tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);

        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => $price,
            'stock' => $stock,
        ]);

        return [$product, $sku];
    }

    /**
     * 加入购物车并返回购物车项 ID
     */
    private function addToCart(Sku $sku, int $qty): int
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $this->user->id], ['status' => true]);

        return $cart->items()->create([
            'product_id' => $sku->product_id,
            'sku_id' => $sku->id,
            'qty' => $qty,
            'price_at_add' => $sku->price,
        ])->getKey();
    }

    /**
     * 发放券到用户并返回持券实例
     */
    private function giveCoupon(Coupon $coupon, ?User $user = null, ?Carbon $expiredAt = null): CouponUser
    {
        return CouponUser::query()->create([
            'coupon_id' => $coupon->getKey(),
            'user_id' => ($user ?? $this->user)->getKey(),
            'expired_at' => $expiredAt ?? now()->addDay(),
        ]);
    }

    /**
     * 带租户头的请求
     */
    private function api(Tenant $tenant): static
    {
        return $this->actingAs($this->user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id);
    }

    // ========================================
    // 购物车下单带券
    // ========================================

    public function test_cart_preview_and_checkout_apply_coupon(): void
    {
        $itemId = $this->addToCart($this->sku, 1);
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id, 'value' => 20.00]);
        $couponUser = $this->giveCoupon($coupon);

        // 结算预览：goods - coupon = payable
        $previewResponse = $this->api($this->tenant)->postJson('/api/mall/cart/preview', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ]);

        $previewResponse->assertOk();
        $this->assertSame('100.00', (string) $previewResponse->json('goods_amount'));
        $this->assertSame('20.00', (string) $previewResponse->json('coupon_discount'));
        $this->assertSame('0.00', (string) $previewResponse->json('freight'));
        $this->assertSame('80.00', (string) $previewResponse->json('payable_amount'));

        // 下单：预览与下单抵扣一致
        $createResponse = $this->api($this->tenant)->postJson('/api/mall/cart/checkout', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ]);

        $createResponse->assertCreated();

        $order = Order::query()->latest('id')->first();
        $this->assertSame('100.00', (string) $order->amount);
        $this->assertSame('20.00', (string) $order->coupon_discount);
        $this->assertSame(80.0, $order->total_amount);

        // 三表写入：持券实例已核销 + 用券记录落库
        $this->assertTrue((bool) $couponUser->fresh()->is_used);
        $this->assertDatabaseHas('coupon_order', [
            'order_id' => $order->getKey(),
            'coupon_id' => $coupon->getKey(),
            'coupon_user_id' => $couponUser->getKey(),
            'discount_amount' => 20.00,
        ]);

        // 订单项级分摊快照（单商品订单：全额落到该项）
        $this->assertSame('20.00', (string) $order->items()->first()->coupon_discount);
    }

    public function test_cart_checkout_writes_item_level_coupon_share(): void
    {
        // 两商品订单（60 / 40），券 20：按小计比例分摊为 12 / 8
        [, $skuA] = $this->makeProductSku($this->tenant, '60.00');
        [, $skuB] = $this->makeProductSku($this->tenant, '40.00');
        $itemIdA = $this->addToCart($skuA, 1);
        $itemIdB = $this->addToCart($skuB, 1);

        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id, 'value' => 20.00]);
        $couponUser = $this->giveCoupon($coupon);

        $this->api($this->tenant)->postJson('/api/mall/cart/checkout', [
            'item_ids' => [$itemIdA, $itemIdB],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ])->assertCreated();

        $order = Order::query()->latest('id')->first();

        // 逐项分摊落库，Σ分摊 = 整单抵扣
        $this->assertSame('12.00', (string) $order->items()->where('price', '60.00')->first()->coupon_discount);
        $this->assertSame('8.00', (string) $order->items()->where('price', '40.00')->first()->coupon_discount);
        $this->assertSame('20.00', (string) $order->coupon_discount);
    }

    public function test_buy_now_checkout_applies_coupon(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id, 'value' => 30.00]);
        $couponUser = $this->giveCoupon($coupon);

        // 立即购买预览
        $previewResponse = $this->api($this->tenant)->postJson('/api/mall/orders/preview', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 2,
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ]);

        $previewResponse->assertOk();
        $this->assertSame('200.00', (string) $previewResponse->json('goods_amount'));
        $this->assertSame('30.00', (string) $previewResponse->json('coupon_discount'));
        $this->assertSame('170.00', (string) $previewResponse->json('payable_amount'));

        // 立即购买下单
        $createResponse = $this->api($this->tenant)->postJson('/api/mall/orders', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 2,
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ]);

        $createResponse->assertCreated();

        $order = Order::query()->latest('id')->first();
        $this->assertSame('30.00', (string) $order->coupon_discount);
        $this->assertSame(170.0, $order->total_amount);
        $this->assertTrue((bool) $couponUser->fresh()->is_used);
    }

    public function test_coupon_base_amount_is_discounted_price(): void
    {
        // 身份折扣 8 折：券基数按折后单价计算（100 × 80% = 80）
        $identity = Identity::create([
            'tenant_id' => $this->tenant->id,
            'name' => '会员',
            'status' => true,
        ]);
        $this->user->identities()->attach($identity->id, [
            'tenant_id' => $this->tenant->id,
            'start_at' => now(),
            'end_at' => null,
        ]);
        $this->product->discounts()->attach($identity->id, ['percent' => 80]);

        $itemId = $this->addToCart($this->sku, 1);
        $coupon = Coupon::factory()->percent()->create([
            'tenant_id' => $this->tenant->id,
            'value' => 50,
            'max_discount' => null,
        ]);
        $couponUser = $this->giveCoupon($coupon);

        $response = $this->api($this->tenant)->postJson('/api/mall/cart/preview', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ]);

        $response->assertOk();
        $this->assertSame('80.00', (string) $response->json('goods_amount'));
        // 折后基数 80 × 50% = 40
        $this->assertSame('40.00', (string) $response->json('coupon_discount'));
        $this->assertSame('40.00', (string) $response->json('payable_amount'));
    }

    public function test_preview_returns_error_for_invalid_coupon(): void
    {
        $itemId = $this->addToCart($this->sku, 1);
        $coupon = Coupon::factory()->fixed()->withMinAmount(200.00)->create(['tenant_id' => $this->tenant->id]);
        $couponUser = $this->giveCoupon($coupon);

        $this->api($this->tenant)->postJson('/api/mall/cart/preview', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ])
            ->assertStatus(400)
            ->assertJsonPath('message', '订单金额未满足使用条件，最低需要 ￥200.00');
    }

    // ========================================
    // 跨店拆单
    // ========================================

    public function test_cross_tenant_coupon_only_applies_to_its_tenant_order(): void
    {
        $otherTenant = Tenant::factory()->create();
        [$otherProduct, $otherSku] = $this->makeProductSku($otherTenant, '50.00');

        $itemId = $this->addToCart($this->sku, 1);
        $otherItemId = $this->addToCart($otherSku, 1);

        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id, 'value' => 20.00]);
        $couponUser = $this->giveCoupon($coupon);

        $this->api($this->tenant)->postJson('/api/mall/cart/checkout', [
            'item_ids' => [$itemId, $otherItemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ])->assertCreated();

        $couponOrder = Order::query()->where('tenant_id', $this->tenant->id)->latest('id')->first();
        $otherOrder = Order::query()->where('tenant_id', $otherTenant->id)->latest('id')->first();

        // 券仅抵扣其所属租户的子订单
        $this->assertSame('20.00', (string) $couponOrder->coupon_discount);
        $this->assertSame(80.0, $couponOrder->total_amount);
        $this->assertSame('0.00', (string) $otherOrder->coupon_discount);
        $this->assertSame(50.0, $otherOrder->total_amount);
    }

    public function test_checkout_rejected_when_coupon_tenant_not_in_cart(): void
    {
        $otherTenant = Tenant::factory()->create();
        $itemId = $this->addToCart($this->sku, 1);

        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $otherTenant->id, 'value' => 20.00]);
        $couponUser = $this->giveCoupon($coupon);

        $this->api($this->tenant)->postJson('/api/mall/cart/checkout', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ])
            ->assertStatus(400)
            ->assertJsonPath('message', '优惠券不适用于所选商品');

        $this->assertDatabaseCount('orders', 0);
        $this->assertFalse((bool) $couponUser->fresh()->is_used);
    }

    // ========================================
    // 并发与重复使用
    // ========================================

    public function test_coupon_cannot_be_used_by_two_orders(): void
    {
        $itemId = $this->addToCart($this->sku, 1);
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id, 'value' => 20.00]);
        $couponUser = $this->giveCoupon($coupon);

        $this->api($this->tenant)->postJson('/api/mall/cart/checkout', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ])->assertCreated();

        // 同一张券再次下单：请求校验即拒绝
        $this->api($this->tenant)->postJson('/api/mall/cart/preview', [
            'item_ids' => [$itemId],
            'fulfillment_type' => FulfillmentType::Virtual->value,
            'coupon_user_id' => $couponUser->getKey(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', '优惠券已被使用');

        $this->assertDatabaseCount('coupon_order', 1);
    }

    // ========================================
    // 可用券接口
    // ========================================

    public function test_available_coupons_returns_applicable_and_inapplicable(): void
    {
        // 可用券：满 50 减 10，商品 100 元满足门槛
        $applicable = Coupon::factory()->fixed()->withMinAmount(50.00)
            ->create(['tenant_id' => $this->tenant->id, 'value' => 10.00, 'name' => '满50减10']);
        $applicableUser = $this->giveCoupon($applicable);

        // 不可用券：满 200 减 30，商品 100 元不满足门槛
        $inapplicable = Coupon::factory()->fixed()->withMinAmount(200.00)
            ->create(['tenant_id' => $this->tenant->id, 'value' => 30.00, 'name' => '满200减30']);
        $inapplicableUser = $this->giveCoupon($inapplicable);

        $response = $this->api($this->tenant)
            ->getJson('/api/campaign/coupons/available?'.http_build_query([
                'items' => [['sku_id' => $this->sku->id, 'qty' => 1]],
            ]));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.tenant_id', $this->tenant->id)
            ->assertJsonCount(2, '0.coupons');

        $coupons = collect($response->json('0.coupons'))->keyBy('coupon_user_id');

        $this->assertTrue($coupons[$applicableUser->getKey()]['applicable']);
        $this->assertSame('10.00', $coupons[$applicableUser->getKey()]['discount_preview']);
        $this->assertSame('100.00', $coupons[$applicableUser->getKey()]['base_amount']);
        $this->assertNull($coupons[$applicableUser->getKey()]['inapplicable_reason']);

        $this->assertFalse($coupons[$inapplicableUser->getKey()]['applicable']);
        $this->assertSame('订单金额未满足使用条件，最低需要 ￥200.00', $coupons[$inapplicableUser->getKey()]['inapplicable_reason']);
    }

    public function test_available_coupons_excludes_used_and_expired(): void
    {
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id]);

        // 已使用
        $used = $this->giveCoupon($coupon);
        $used->update(['is_used' => true, 'used_at' => now()]);

        // 已过期（持券实例维度）
        $this->giveCoupon($coupon, expiredAt: now()->subDay());

        $response = $this->api($this->tenant)
            ->getJson('/api/campaign/coupons/available?'.http_build_query([
                'items' => [['sku_id' => $this->sku->id, 'qty' => 1]],
            ]));

        $response->assertOk()->assertJsonCount(0);
    }

    public function test_available_coupons_requires_items(): void
    {
        $this->api($this->tenant)
            ->getJson('/api/campaign/coupons/available')
            ->assertUnprocessable()
            ->assertJsonPath('message', '必须提供结算商品');
    }

    public function test_available_coupons_excludes_unauthorized_tenant_coupons(): void
    {
        // 用户未授权他租户：他租户的券不应出现
        $otherTenant = Tenant::factory()->create();
        $otherCoupon = Coupon::factory()->fixed()->create(['tenant_id' => $otherTenant->id]);
        $this->giveCoupon($otherCoupon);

        $this->api($this->tenant)
            ->getJson('/api/campaign/coupons/available?'.http_build_query([
                'items' => [['sku_id' => $this->sku->id, 'qty' => 1]],
            ]))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_available_coupons_narrowed_by_tenant_header(): void
    {
        // 用户同时授权两个租户，各持有 1 张券
        $otherTenant = Tenant::factory()->create();
        $this->user->tenants()->attach($otherTenant);
        [, $otherSku] = $this->makeProductSku($otherTenant, '50.00');

        $this->giveCoupon(Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id]));
        $this->giveCoupon(Coupon::factory()->fixed()->create(['tenant_id' => $otherTenant->id]));

        // 带租户头：收窄至该租户，仅返回 1 组
        $this->api($this->tenant)
            ->getJson('/api/campaign/coupons/available?'.http_build_query([
                'items' => [
                    ['sku_id' => $this->sku->id, 'qty' => 1],
                    ['sku_id' => $otherSku->id, 'qty' => 1],
                ],
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.tenant_id', $this->tenant->id);
    }

    public function test_available_coupons_returns_all_authorized_tenants_without_header(): void
    {
        // 用户同时授权两个租户，各持有 1 张券
        $otherTenant = Tenant::factory()->create();
        $this->user->tenants()->attach($otherTenant);
        [, $otherSku] = $this->makeProductSku($otherTenant, '50.00');

        $this->giveCoupon(Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id]));
        $this->giveCoupon(Coupon::factory()->fixed()->create(['tenant_id' => $otherTenant->id]));

        // 不带租户头：按全部授权租户返回 2 组
        $this->actingAs($this->user)
            ->getJson('/api/campaign/coupons/available?'.http_build_query([
                'items' => [
                    ['sku_id' => $this->sku->id, 'qty' => 1],
                    ['sku_id' => $otherSku->id, 'qty' => 1],
                ],
            ]))
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_available_coupons_returns_type_label(): void
    {
        $coupon = Coupon::factory()->percent()->create([
            'tenant_id' => $this->tenant->id,
            'value' => 10,
            'max_discount' => null,
        ]);
        $this->giveCoupon($coupon);

        $response = $this->api($this->tenant)
            ->getJson('/api/campaign/coupons/available?'.http_build_query([
                'items' => [['sku_id' => $this->sku->id, 'qty' => 1]],
            ]));

        $response->assertOk()
            ->assertJsonPath('0.coupons.0.type.value', CouponType::Percent->value)
            ->assertJsonPath('0.coupons.0.type.label', '百分比')
            // 100 × 10% = 10
            ->assertJsonPath('0.coupons.0.discount_preview', '10.00');
    }
}
