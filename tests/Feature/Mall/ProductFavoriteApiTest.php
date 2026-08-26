<?php

namespace Tests\Feature\Mall;

use App\Models\Mall\Product;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Tenant;
use App\Models\User\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductFavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        StoreConfigure::create([
            'tenant_id' => $this->tenant->id,
            'store_name' => 'Test Store',
            'cover' => null,
            'enabled' => true,
        ]);
    }

    /**
     * 附带当前租户请求头（store.opened 中间件需要）
     */
    private function withTenantHeader(): static
    {
        return $this->withHeader('X-Tenant-Id', (string) $this->tenant->id);
    }

    /**
     * 直接向 favorites 表写入收藏记录（可精确控制时间）
     */
    private function addFavoriteRow(User $user, Product $product, DateTimeInterface $at): void
    {
        DB::table('favorites')->insert([
            'user_id' => $user->id,
            'favoriteable_id' => $product->id,
            'favoriteable_type' => Product::class,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function favoriteCount(User $user, Product $product): int
    {
        return DB::table('favorites')
            ->where('user_id', $user->id)
            ->where('favoriteable_id', $product->id)
            ->count();
    }

    // ─── 认证 ──────────────────────────────────────────────────────

    public function test_favorite_endpoints_require_authentication(): void
    {
        $this->withTenantHeader()->getJson('/api/mall/favorites')->assertUnauthorized();

        $product = Product::factory()->create();

        $this->withTenantHeader()
            ->postJson("/api/mall/products/{$product->id}/favorite")
            ->assertUnauthorized();

        $this->withTenantHeader()
            ->getJson("/api/mall/products/{$product->id}/favorite")
            ->assertUnauthorized();
    }

    // ─── GET /api/mall/favorites ───────────────────────────────────

    public function test_can_list_own_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $user->favorite($product);
        $otherUser->favorite($otherProduct);

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->getJson('/api/mall/favorites');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.goods_id', $product->id)
            ->assertJsonPath('page.total', 1);
    }

    public function test_favorite_list_orders_by_favorited_at_desc(): void
    {
        $user = User::factory()->create();

        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $this->addFavoriteRow($user, $productA, now()->subHours(2));
        $this->addFavoriteRow($user, $productB, now()->subHour());

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->getJson('/api/mall/favorites');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.goods_id', $productB->id)
            ->assertJsonPath('data.1.goods_id', $productA->id);
    }

    public function test_favorite_list_excludes_down_products(): void
    {
        $user = User::factory()->create();

        $upProduct = Product::factory()->create();
        $downProduct = Product::factory()->down()->create();

        $user->favorite($upProduct);
        $user->favorite($downProduct);

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->getJson('/api/mall/favorites');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.goods_id', $upProduct->id);
    }

    public function test_favorite_list_respects_pagination(): void
    {
        $user = User::factory()->create();

        $products = Product::factory()->count(3)->create();

        foreach ($products as $index => $product) {
            $this->addFavoriteRow($user, $product, now()->subHours(3 - $index));
        }

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->getJson('/api/mall/favorites?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            // 最新收藏的排最前
            ->assertJsonPath('data.0.goods_id', $products->last()->id)
            ->assertJsonPath('data.1.goods_id', $products->skip(1)->last()->id)
            ->assertJsonPath('page.per_page', 2)
            ->assertJsonPath('page.total', 3)
            ->assertJsonPath('page.total_page', 2)
            ->assertJsonPath('page.has_more', true);
    }

    // ─── POST /api/mall/products/{product}/favorite ────────────────

    public function test_toggle_favorite_adds_then_removes(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->postJson("/api/mall/products/{$product->id}/favorite");

        $response->assertOk()->assertJsonPath('is_favorited', true);
        $this->assertSame(1, $this->favoriteCount($user, $product));

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->postJson("/api/mall/products/{$product->id}/favorite");

        $response->assertOk()->assertJsonPath('is_favorited', false);
        $this->assertSame(0, $this->favoriteCount($user, $product));
    }

    public function test_toggle_returns_404_for_down_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->down()->create();

        $this->actingAs($user)
            ->withTenantHeader()
            ->postJson("/api/mall/products/{$product->id}/favorite")
            ->assertNotFound()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '商品不存在');

        $this->assertSame(0, $this->favoriteCount($user, $product));
    }

    public function test_toggle_returns_404_for_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withTenantHeader()
            ->postJson('/api/mall/products/99999/favorite')
            ->assertNotFound();
    }

    // ─── GET /api/mall/products/{product}/favorite ─────────────────

    public function test_check_favorite_status(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->getJson("/api/mall/products/{$product->id}/favorite");

        $response->assertOk()->assertJsonPath('is_favorited', false);

        $user->favorite($product);

        $response = $this->actingAs($user)
            ->withTenantHeader()
            ->getJson("/api/mall/products/{$product->id}/favorite");

        $response->assertOk()->assertJsonPath('is_favorited', true);
    }

    public function test_check_returns_404_for_down_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->down()->create();

        $this->actingAs($user)
            ->withTenantHeader()
            ->getJson("/api/mall/products/{$product->id}/favorite")
            ->assertNotFound()
            ->assertJsonPath('message', '商品不存在');
    }
}
