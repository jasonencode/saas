<?php

namespace Tests\Feature\Content;

use App\Enums\Content\CategoryType;
use App\Models\Content\ContentCategory;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id);
    }

    // ─── GET /api/contents/categories ───────────────────────────────

    public function test_can_list_content_categories(): void
    {
        ContentCategory::factory()->for($this->tenant, 'tenant')->count(3)->create();

        $response = $this->getJson('/api/contents/categories');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => ['category_id', 'level', 'name', 'description', 'cover'],
            ])
            ->assertJsonCount(3);
    }

    public function test_list_excludes_disabled_categories(): void
    {
        ContentCategory::factory()->for($this->tenant, 'tenant')->count(2)->create();
        ContentCategory::factory()->for($this->tenant, 'tenant')->disabled()->create();

        $response = $this->getJson('/api/contents/categories');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    public function test_list_excludes_product_type_categories(): void
    {
        ContentCategory::factory()->for($this->tenant, 'tenant')->count(2)->create();
        DB::table('categories')->insert([
            'name' => '商品分类',
            'type' => CategoryType::Product->value,
            'level' => 1,
            'status' => true,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/contents/categories');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    public function test_list_returns_empty_when_no_categories(): void
    {
        $response = $this->getJson('/api/contents/categories');

        $response->assertOk()
            ->assertJsonCount(0);
    }

    public function test_list_builds_tree_with_single_query(): void
    {
        $root = ContentCategory::factory()->for($this->tenant, 'tenant')->create(['name' => '一级', 'level' => 1]);
        $child = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($root)->create(['name' => '二级']);
        $grandchild = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($child)->create(['name' => '三级']);

        DB::enableQueryLog();
        $response = $this->getJson('/api/contents/categories');
        $categoryQueries = collect(DB::getQueryLog())
            ->filter(static fn (array $query): bool => str_contains($query['query'], 'from "categories"'));
        DB::disableQueryLog();

        $response->assertOk()
            ->assertJsonPath('0.category_id', $root->id)
            ->assertJsonPath('0.children.0.category_id', $child->id)
            ->assertJsonPath('0.children.0.children.0.category_id', $grandchild->id);

        $this->assertSame(1, $categoryQueries->count());
    }

    // ─── GET /api/contents/categories/{category} ────────────────────

    public function test_can_show_category_detail(): void
    {
        $category = ContentCategory::factory()->for($this->tenant, 'tenant')->create([
            'name' => '测试分类',
            'description' => '测试描述',
        ]);

        $response = $this->getJson("/api/contents/categories/{$category->id}");

        $response->assertOk()
            ->assertJson([
                'category_id' => $category->id,
                'name' => '测试分类',
                'description' => '测试描述',
            ]);
    }

    public function test_category_detail_includes_children(): void
    {
        $parent = ContentCategory::factory()->for($this->tenant, 'tenant')->create(['name' => '父分类']);
        $child = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($parent)->create(['name' => '子分类']);

        $response = $this->getJson("/api/contents/categories/{$parent->id}");

        $response->assertOk()
            ->assertJsonPath('name', '父分类')
            ->assertJsonStructure([
                'children' => [
                    '*' => ['category_id', 'name'],
                ],
            ])
            ->assertJsonPath('children.0.name', '子分类');
    }

    public function test_category_detail_excludes_disabled_children(): void
    {
        $parent = ContentCategory::factory()->for($this->tenant, 'tenant')->create(['name' => '父分类']);
        ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($parent)->create(['name' => '启用子分类']);
        ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($parent)->disabled()->create(['name' => '禁用子分类']);

        $response = $this->getJson("/api/contents/categories/{$parent->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'children');
    }

    public function test_show_returns_404_for_disabled_category(): void
    {
        $category = ContentCategory::factory()->for($this->tenant, 'tenant')->disabled()->create();

        $response = $this->getJson("/api/contents/categories/{$category->id}");

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_product_type_category(): void
    {
        DB::table('categories')->insert([
            'name' => '商品分类',
            'type' => CategoryType::Product->value,
            'level' => 1,
            'status' => true,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/contents/categories/1');

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/contents/categories/99999');

        $response->assertNotFound();
    }

    // ─── Category Model: Level Validation ───────────────────────────

    public function test_category_max_three_levels(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('最多可以创建三级分类');

        $level1 = ContentCategory::factory()->for($this->tenant, 'tenant')->create(['level' => 1]);
        $level2 = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($level1)->create();
        $level3 = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($level2)->create();
        // 第4层应该抛出异常
        ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($level3)->create();
    }

    public function test_category_deleting_cascades_to_children(): void
    {
        $parent = ContentCategory::factory()->for($this->tenant, 'tenant')->create();
        $child = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($parent)->create();
        $grandchild = ContentCategory::factory()->for($this->tenant, 'tenant')->childOf($child)->create();

        $parent->delete();

        $this->assertSoftDeleted('categories', ['id' => $parent->id]);
        $this->assertSoftDeleted('categories', ['id' => $child->id]);
        $this->assertSoftDeleted('categories', ['id' => $grandchild->id]);
    }

    public function test_content_category_sets_type_on_create(): void
    {
        $category = ContentCategory::factory()->for($this->tenant, 'tenant')->create();

        $this->assertSame(CategoryType::Content, $category->type);
    }

    public function test_content_category_global_scope_filters_content_type(): void
    {
        ContentCategory::factory()->for($this->tenant, 'tenant')->count(2)->create();
        DB::table('categories')->insert([
            'name' => '商品分类',
            'type' => CategoryType::Product->value,
            'level' => 1,
            'status' => true,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ContentCategory 查询只返回 content 类型
        $this->assertCount(2, ContentCategory::all());

        // 直接查 categories 表有 3 条
        $this->assertSame(3, DB::table('categories')->count());
    }
}
