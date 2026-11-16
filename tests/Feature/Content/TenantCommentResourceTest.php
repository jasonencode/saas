<?php

namespace Tests\Feature\Content;

use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Content\Resources\Comments\Pages\ManageComments;
use App\Models\Content\Comment;
use App\Models\Content\Content;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Policies\Content\CommentPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\GrantsPermissions;
use Tests\TestCase;

/**
 * 租户端评论管理按租户隔离
 */
class TenantCommentResourceTest extends TestCase
{
    use GrantsPermissions,
        RefreshDatabase;

    public function test_tenant_comment_table_only_lists_own_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => ['modules' => [AvailableModule::Content->value]],
        ]);

        $comment = $this->makeComment($tenant);
        $otherComment = $this->makeComment(Tenant::factory()->create());

        $admin = Administrator::factory()->tenantAdmin()->create();
        $admin->tenants()->attach($tenant);

        $this->actingAs($admin, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
        Filament::bootCurrentPanel();
        $this->grantPolicyPermissions($admin, [CommentPolicy::class]);

        Livewire::test(ManageComments::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$comment])
            ->assertCanNotSeeTableRecords([$otherComment]);
    }

    private function makeComment(Tenant $tenant): Comment
    {
        $content = Content::factory()->for($tenant, 'tenant')->create();

        return $content->comments()->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => User::factory()->create()->getKey(),
            'content' => '评论内容',
            'star' => 5,
            'status' => true,
        ]);
    }
}
