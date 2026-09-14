<?php

namespace Tests\Feature\Content;

use App\Enums\Content\TagType;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use App\Models\Content\ContentTag;
use App\Models\Content\SinglePage;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SinglePageAndTagApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
    }

    // ─── GET /api/contents/single-pages ──────────────────────────

    public function test_can_list_enabled_single_pages(): void
    {
        $this->makeSinglePage(['title' => '关于我们', 'status' => true]);
        $this->makeSinglePage(['title' => '隐私政策', 'status' => true]);
        $this->makeSinglePage(['title' => '已停用页面', 'status' => false]);

        $this->getJson('/api/contents/single-pages')
            ->assertOk()
            ->assertJsonStructure(['list', 'page' => ['current', 'total']]);

        $titles = collect($this->getJson('/api/contents/single-pages')->json('list'))->pluck('title');

        $this->assertCount(2, $titles);
        $this->assertNotContains('已停用页面', $titles);
    }

    public function test_single_page_list_returns_empty_when_none(): void
    {
        $this->getJson('/api/contents/single-pages')
            ->assertOk()
            ->assertJsonPath('page.total', 0);
    }

    // ─── GET /api/contents/single-pages/{slug} ───────────────────

    public function test_can_show_single_page_by_slug(): void
    {
        $this->makeSinglePage([
            'title' => '关于我们',
            'slug' => 'about-us',
            'content' => '这是关于我们页面的内容',
            'status' => true,
        ]);

        $this->getJson('/api/contents/single-pages/about-us')
            ->assertOk()
            ->assertJsonPath('title', '关于我们')
            ->assertJsonPath('slug', 'about-us')
            ->assertJsonPath('content', '这是关于我们页面的内容');
    }

    public function test_single_page_show_returns_404_for_missing_slug(): void
    {
        $this->getJson('/api/contents/single-pages/not-exists')
            ->assertNotFound();
    }

    public function test_single_page_show_returns_404_for_disabled_page(): void
    {
        $this->makeSinglePage(['slug' => 'disabled-page', 'status' => false]);

        $this->getJson('/api/contents/single-pages/disabled-page')
            ->assertNotFound();
    }

    // ─── GET /api/contents/tags ──────────────────────────────────

    public function test_can_list_content_tags_with_contents_count(): void
    {
        $tag = $this->makeTag('热卖');
        $this->makeTag('新品');

        $this->getJson('/api/contents/tags')
            ->assertOk()
            ->assertJsonCount(2);

        $tags = collect($this->getJson('/api/contents/tags')->json());

        $this->assertSame('热卖', $tags->firstWhere('tag_id', $tag->id)['name']);
        $this->assertSame(0, $tags->firstWhere('tag_id', $tag->id)['contents_count']);
    }

    public function test_tag_list_counts_associated_contents(): void
    {
        $tag = $this->makeTag('有关联');
        $category = ContentCategory::factory()->for($this->tenant, 'tenant')->create();

        Content::factory()->for($this->tenant, 'tenant')->create([
            'category_id' => $category->id,
        ])->tags()->attach($tag->id);

        $tags = collect($this->getJson('/api/contents/tags')->json());

        $this->assertSame(1, $tags->firstWhere('tag_id', $tag->id)['contents_count']);
    }

    public function test_tag_list_excludes_non_content_type_tags(): void
    {
        // ContentTag::creating 会强制 type=content，改用 DB 直插商品类型标签
        DB::table('tags')->insert([
            'tenant_id' => $this->tenant->id,
            'type' => TagType::Product->value,
            'name' => '商品标签',
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/contents/tags')
            ->assertOk()
            ->assertJsonCount(0);
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    private function makeSinglePage(array $attributes = []): SinglePage
    {
        return SinglePage::create([
            'tenant_id' => $this->tenant->id,
            'title' => '测试单页',
            'slug' => 'test-page-'.uniqid(),
            'content' => '单页内容',
            'status' => true,
            ...$attributes,
        ]);
    }

    private function makeTag(string $name): ContentTag
    {
        // tags 表没有 status 字段，全局 scope 只按 type=content 过滤
        return ContentTag::create(['name' => $name]);
    }
}
