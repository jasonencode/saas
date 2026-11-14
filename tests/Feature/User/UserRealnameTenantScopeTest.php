<?php

namespace Tests\Feature\User;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Filament\Tenant\Clusters\User\Resources\UserRealnames\UserRealnameResource;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRealnameTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_binding_is_limited_to_current_tenant(): void
    {
        [$tenant, $ownRealname] = $this->makeTenantRealname();
        [, $otherRealname] = $this->makeTenantRealname();

        $this->authenticateTenantPanel($tenant);

        $query = UserRealnameResource::getRecordRouteBindingEloquentQuery();

        $this->assertNotNull($query->find($ownRealname->getKey()));
        $this->assertNull($query->find($otherRealname->getKey()));
    }

    public function test_list_query_is_limited_to_current_tenant(): void
    {
        [$tenant, $ownRealname] = $this->makeTenantRealname();
        $this->makeTenantRealname();

        $this->authenticateTenantPanel($tenant);

        $this->assertSame(
            [$ownRealname->getKey()],
            UserRealnameResource::getEloquentQuery()->pluck('id')->all(),
        );
    }

    /**
     * 构造一个租户及其用户提交的实名认证
     *
     * @return array{Tenant, UserRealname} 租户与实名认证记录
     */
    private function makeTenantRealname(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $realname = UserRealname::create([
            'user_id' => $user->id,
            'type' => RealnameType::Personal,
            'status' => RealnameStatus::Pending,
            'name' => '张三',
            'id_card_number' => '110101199001011234',
        ]);

        return [$tenant, $realname];
    }

    /**
     * 以租户管理员身份进入租户面板
     */
    private function authenticateTenantPanel(Tenant $tenant): void
    {
        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
    }
}
