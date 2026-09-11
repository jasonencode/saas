<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Models\Mall\Cart;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\ProductDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Product $product;

    private Sku $sku;

    private Identity $identity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->for($this->tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);
        $this->sku = Sku::factory()->create([
            'product_id' => $this->product->id,
            'price' => '99.90',
            'stock' => 100,
        ]);
        $this->identity = Identity::create([
            'tenant_id' => $this->tenant->id,
            'name' => '会员',
            'status' => true,
        ]);
    }

    /**
     * 给用户绑定有效身份
     */
    private function attachIdentity(?string $endAt = null): void
    {
        $this->user->identities()->attach($this->identity->id, [
            'tenant_id' => $this->tenant->id,
            'start_at' => now(),
            'end_at' => $endAt,
        ]);
    }

    public function test_apply_percent_rounds_to_cents(): void
    {
        $service = service(ProductDiscountService::class);

        $this->assertSame('79.92', $service->applyPercent('99.90', 80));
        $this->assertSame('50.00', $service->applyPercent('100.00', 50));
        $this->assertSame('0.33', $service->applyPercent('1.00', 33));
    }

    public function test_price_for_returns_original_price_without_identity(): void
    {
        $price = service(ProductDiscountService::class)->priceFor($this->user, $this->sku);

        $this->assertSame('99.90', $price);
    }

    public function test_price_for_returns_discounted_price_with_identity(): void
    {
        $this->attachIdentity();
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        $price = service(ProductDiscountService::class)->priceFor($this->user, $this->sku);

        $this->assertSame('79.92', $price);
    }

    public function test_expired_identity_gets_no_discount(): void
    {
        $this->attachIdentity(endAt: now()->subDay()->format('Y-m-d H:i:s'));
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        $price = service(ProductDiscountService::class)->priceFor($this->user, $this->sku);

        $this->assertSame('99.90', $price);
    }

    public function test_cross_tenant_identity_gets_no_discount(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherIdentity = Identity::create([
            'tenant_id' => $otherTenant->id,
            'name' => '他店会员',
            'status' => true,
        ]);

        $this->user->identities()->attach($otherIdentity->id, [
            'tenant_id' => $otherTenant->id,
            'start_at' => now(),
            'end_at' => null,
        ]);
        $this->product->discounts()->attach($otherIdentity->id, ['percent' => 80]);

        $price = service(ProductDiscountService::class)->priceFor($this->user, $this->sku);

        $this->assertSame('99.90', $price);
    }

    public function test_batch_percent_map_matches_single_lookup(): void
    {
        $this->attachIdentity();
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        $service = service(ProductDiscountService::class);

        $this->assertSame(
            $service->percentFor($this->user, $this->product),
            $service->percentForProducts($this->user, collect([$this->product]))[$this->product->id] ?? null,
        );
    }

    public function test_order_item_dto_accepts_price_override(): void
    {
        $dto = OrderItemDto::make($this->sku, 2, price: '79.92');

        $this->assertSame('79.92', $dto->price);
        $this->assertSame('159.84', $dto->getAmount());

        $default = OrderItemDto::make($this->sku, 1);
        $this->assertSame('99.90', $default->price);
    }

    public function test_buy_now_preview_and_order_amount_match_with_discount(): void
    {
        $this->attachIdentity();
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        // 立即购买预览
        $previewResponse = $this->actingAs($this->user)
            ->withHeader('X-Tenant-Id', (string) $this->tenant->id)
            ->postJson('/api/mall/orders/preview', [
                'orderable_type' => 'sku',
                'orderable_id' => $this->sku->id,
                'qty' => 2,
                'fulfillment_type' => FulfillmentType::Virtual->value,
            ]);

        $previewResponse->assertOk();
        $this->assertSame('159.84', (string) $previewResponse->json('total_amount'));

        // 立即购买下单
        $createResponse = $this->actingAs($this->user)
            ->withHeader('X-Tenant-Id', (string) $this->tenant->id)
            ->postJson('/api/mall/orders', [
                'orderable_type' => 'sku',
                'orderable_id' => $this->sku->id,
                'qty' => 2,
                'fulfillment_type' => FulfillmentType::Virtual->value,
            ]);

        $createResponse->assertCreated();

        $order = Order::query()->latest('id')->first();
        $this->assertSame('159.84', (string) $order->amount);
        $this->assertSame('79.92', (string) $order->items->first()->price);
    }

    public function test_cart_preview_and_order_amount_match_with_discount(): void
    {
        $this->attachIdentity();
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        $cart = Cart::create(['user_id' => $this->user->id, 'status' => true]);
        $cart->items()->create([
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'qty' => 2,
            'price_at_add' => $this->sku->price,
        ]);

        $itemId = $cart->items()->first()->id;

        // 结算预览
        $previewResponse = $this->actingAs($this->user)
            ->withHeader('X-Tenant-Id', (string) $this->tenant->id)
            ->postJson('/api/mall/cart/preview', [
                'item_ids' => [$itemId],
                'fulfillment_type' => FulfillmentType::Virtual->value,
            ]);

        $previewResponse->assertOk();
        $this->assertSame('159.84', (string) $previewResponse->json('total_amount'));

        // 预览明细金额必须与合计同口径（回归：曾被集合下标污染成 0%/1% 折扣价）
        $this->assertSame('79.92', $previewResponse->json('items.0.price'));
        $this->assertSame('99.90', $previewResponse->json('items.0.original_price'));
        $this->assertSame('159.84', $previewResponse->json('items.0.sub_total'));

        // 购物车下单
        $createResponse = $this->actingAs($this->user)
            ->withHeader('X-Tenant-Id', (string) $this->tenant->id)
            ->postJson('/api/mall/cart/checkout', [
                'item_ids' => [$itemId],
                'fulfillment_type' => FulfillmentType::Virtual->value,
            ]);

        $createResponse->assertCreated();

        $order = Order::query()->latest('id')->first();
        $this->assertSame('159.84', (string) $order->amount);
        $this->assertSame('79.92', (string) $order->items->first()->price);
    }

    public function test_product_detail_returns_discount_price_for_token_user(): void
    {
        $this->attachIdentity();
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        $token = $this->user->createToken('test')->plainTextToken;

        // 详情为公开路由，需 guess:sanctum 才能识别 Bearer Token 用户（回归：曾恒不返回 discount_price）
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/mall/products/{$this->product->id}")
            ->assertOk()
            ->assertJsonPath('discount_price', '79.92');
    }

    public function test_product_detail_omits_discount_price_for_guest(): void
    {
        $this->attachIdentity();
        $this->product->discounts()->attach($this->identity->id, ['percent' => 80]);

        $this->getJson("/api/mall/products/{$this->product->id}")
            ->assertOk()
            ->assertJsonMissingPath('discount_price');
    }
}
