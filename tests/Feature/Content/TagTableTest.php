<?php

namespace Tests\Feature\Content;

use App\Enums\System\AvailableModule;
use App\Filament\Backend\Clusters\Content\Resources\Tags\Pages\ManageTags as BackendManageTags;
use App\Filament\Tenant\Clusters\Content\Resources\Tags\Pages\ManageTags as TenantManageTags;
use App\Models\Content\ContentTag;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class TagTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_content_tag_table_renders_tenant_column_and_filter(): void
    {
        $this->authenticateBackendPanel();

        $tag = $this->makeTag(Tenant::factory()->create(), '后端标签');

        Livewire::test(BackendManageTags::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$tag])
            ->assertTableColumnVisible('tenant.name')
            ->assertTableFilterVisible('tenant_id');
    }

    public function test_tenant_content_tag_table_hides_tenant_column_and_filter(): void
    {
        $tenant = $this->authenticateTenantPanel(AvailableModule::Content);

        $tag = $this->makeTag($tenant, '租户标签');

        Livewire::test(TenantManageTags::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$tag])
            ->assertTableColumnHidden('tenant.name')
            ->assertTableFilterHidden('tenant_id');
    }

    private function makeTag(Tenant $tenant, string $name): ContentTag
    {
        return ContentTag::create([
            'tenant_id' => $tenant->getKey(),
            'name' => $name,
            'sort' => 0,
        ]);
    }

    private function authenticateBackendPanel(): void
    {
        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
    }

    private function authenticateTenantPanel(AvailableModule $module): Tenant
    {
        $tenant = Tenant::factory()->create([
            'config' => ['modules' => [$module->value]],
        ]);
        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
        Gate::before(fn (): bool => true);

        return $tenant;
    }
}
