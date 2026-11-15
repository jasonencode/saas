<?php

namespace Tests\Feature\Content;

use App\Models\Content\Content;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id);
    }

    // ─── GET /api/contents ───────────────────────────────────────────

    public function test_can_list_enabled_contents(): void
    {
        Content::factory()->for($this->tenant, 'tenant')->count(3)->create();

        $response = $this->getJson('/api/contents');

        $response->assertOk()
            ->assertJsonStructure([
                'list' => [
                    '*' => ['content_id', 'title', 'cover', 'created_at'],
                ],
                'page' => ['current', 'total_page', 'per_page', 'has_more', 'total'],
            ])
            ->assertJsonCount(3, 'list');
    }

    public function test_list_only_returns_current_tenant_contents(): void
    {
        Content::factory()->for($this->tenant, 'tenant')->count(2)->create();
        Content::factory()->for(Tenant::factory()->create(), 'tenant')->create();

        $this->getJson('/api/contents')
            ->assertOk()
            ->assertJsonCount(2, 'list');
    }

    public function test_show_returns_404_for_another_tenant_content(): void
    {
        $content = Content::factory()->for(Tenant::factory()->create(), 'tenant')->create();

        $this->getJson("/api/contents/{$content->id}")->assertNotFound();
    }

    public function test_list_excludes_disabled_contents(): void
    {
        Content::factory()->for($this->tenant, 'tenant')->count(2)->create();
        Content::factory()->for($this->tenant, 'tenant')->disabled()->create();

        $response = $this->getJson('/api/contents');

        $response->assertOk()
            ->assertJsonCount(2, 'list');
    }

    public function test_list_excludes_soft_deleted_contents(): void
    {
        Content::factory()->for($this->tenant, 'tenant')->count(2)->create();
        Content::factory()->for($this->tenant, 'tenant')->create()->delete();

        $response = $this->getJson('/api/contents');

        $response->assertOk()
            ->assertJsonCount(2, 'list');
    }

    public function test_list_returns_empty_when_no_contents(): void
    {
        $response = $this->getJson('/api/contents');

        $response->assertOk()
            ->assertJsonCount(0, 'list');
    }

    public function test_list_has_pagination_structure(): void
    {
        Content::factory()->for($this->tenant, 'tenant')->count(20)->create();

        $response = $this->getJson('/api/contents');

        $perPage = config('custom.pagination.default_per_page', 15);

        $response->assertOk()
            ->assertJsonCount($perPage, 'list')
            ->assertJsonPath('page.current', 1)
            ->assertJsonPath('page.per_page', $perPage)
            ->assertJsonPath('page.total', 20)
            ->assertJsonPath('page.has_more', true);
    }

    // ─── GET /api/contents/{content} ────────────────────────────────

    public function test_can_show_content_detail(): void
    {
        $content = Content::factory()->for($this->tenant, 'tenant')->create([
            'title' => '测试标题',
            'sub_title' => '测试副标题',
            'description' => '测试描述',
            'author' => '测试作者',
            'source' => '测试来源',
            'content' => '测试正文内容',
        ]);

        $response = $this->getJson("/api/contents/{$content->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'content_id',
                'title',
                'sub_title',
                'description',
                'author',
                'source',
                'content',
                'cover',
                'views',
                'status',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'content_id' => $content->id,
                'title' => '测试标题',
                'sub_title' => '测试副标题',
                'description' => '测试描述',
                'author' => '测试作者',
                'source' => '测试来源',
                'content' => '测试正文内容',
            ]);
    }

    public function test_show_increments_views(): void
    {
        $content = Content::factory()->for($this->tenant, 'tenant')->create(['views' => 10]);

        $this->getJson("/api/contents/{$content->id}");
        $this->getJson("/api/contents/{$content->id}");
        $this->getJson("/api/contents/{$content->id}");

        $content->refresh();
        $this->assertSame(13, $content->views);
    }

    public function test_show_returns_404_for_disabled_content(): void
    {
        $content = Content::factory()->for($this->tenant, 'tenant')->disabled()->create();

        $response = $this->getJson("/api/contents/{$content->id}");

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_content(): void
    {
        $response = $this->getJson('/api/contents/99999');

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_soft_deleted_content(): void
    {
        $content = Content::factory()->for($this->tenant, 'tenant')->create();
        $content->delete();

        $response = $this->getJson("/api/contents/{$content->id}");

        $response->assertNotFound();
    }
}
