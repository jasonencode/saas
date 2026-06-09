<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;
use App\Models\Mall\Sku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MallApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api/mall ────────────────────────────────────────────

    public function test_mall_index_returns_success(): void
    {
        $response = $this->getJson('/api/mall');

        $response->assertOk();
    }

    // ─── GET /api/mall/brands ─────────────────────────────────────

    public function test_brands_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/mall/brands');

        $response->assertOk();
    }

    // ─── GET /api/mall/banners ────────────────────────────────────

    public function test_banners_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/mall/banners');

        $response->assertOk();
    }

    // ─── GET /api/mall/categories ─────────────────────────────────

    public function test_can_list_product_categories(): void
    {
        ProductCategory::factory()->count(3)->create();

        $response = $this->getJson('/api/mall/categories');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_category_list_excludes_disabled(): void
    {
        ProductCategory::factory()->count(2)->create();
        ProductCategory::factory()->disabled()->create();

        $response = $this->getJson('/api/mall/categories');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    // ─── GET /api/mall/categories/{category} ──────────────────────

    public function test_can_show_product_category(): void
    {
        $category = ProductCategory::factory()->create([
            'name' => '测试分类',
        ]);

        $response = $this->getJson("/api/mall/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('name', '测试分类');
    }

    public function test_category_show_returns_404_when_disabled(): void
    {
        $category = ProductCategory::factory()->disabled()->create();

        $response = $this->getJson("/api/mall/categories/{$category->id}");

        $response->assertNotFound();
    }

    public function test_category_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/mall/categories/99999');

        $response->assertNotFound();
    }

    // ─── GET /api/mall/products ───────────────────────────────────

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();
        Sku::factory()->count(3)->create([
            'product_id' => Product::first()->id,
        ]);

        $response = $this->getJson('/api/mall/products');

        $response->assertOk();
    }

    public function test_product_list_excludes_down_products(): void
    {
        Product::factory()->count(2)->create();
        Product::factory()->down()->create();

        $response = $this->getJson('/api/mall/products');

        $response->assertOk();
    }

    // ─── GET /api/mall/products/{product} ─────────────────────────

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create([
            'name' => '测试商品',
            'description' => '商品描述',
        ]);
        Sku::factory()->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/mall/products/{$product->id}");

        $response->assertOk();
    }

    public function test_product_show_returns_404_when_down(): void
    {
        $product = Product::factory()->down()->create();

        $response = $this->getJson("/api/mall/products/{$product->id}");

        $response->assertNotFound();
    }

    public function test_product_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/mall/products/99999');

        $response->assertNotFound();
    }

    // ─── Cart (requires auth) ─────────────────────────────────────

    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/mall/cart')->assertUnauthorized();
        $this->postJson('/api/mall/cart/add')->assertUnauthorized();
    }

    // ─── Orders (requires auth) ───────────────────────────────────

    public function test_orders_requires_authentication(): void
    {
        $this->getJson('/api/mall/orders')->assertUnauthorized();
        $this->postJson('/api/mall/orders')->assertUnauthorized();
    }
}
