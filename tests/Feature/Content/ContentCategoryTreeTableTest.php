<?php

namespace Tests\Feature\Content;

use App\Enums\System\AvailableModule;
use App\Filament\Backend\Clusters\Content\Resources\Categories\Pages\ManageCategories as BackendManageCategories;
use App\Filament\Tenant\Clusters\Content\Resources\Categories\Pages\ManageCategories as TenantManageCategories;
use App\Models\Content\ContentCategory;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ContentCategoryTreeTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_category_table_renders_roots_and_expands_children(): void
    {
        $this->authenticateBackendPanel();

        [$parent, $child] = $this->makeCategories(Tenant::factory()->create());

        Livewire::test(BackendManageCategories::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$parent])
            ->assertCanNotSeeTableRecords([$child])
            ->call('toggleRowExpansion', (string) $parent->getKey())
            ->assertCanSeeTableRecords([$parent, $child]);
    }

    public function test_tenant_category_table_renders_roots_and_expands_children(): void
    {
        $tenant = $this->authenticateTenantPanel(AvailableModule::Content);

        [$parent, $child] = $this->makeCategories($tenant);

        Livewire::test(TenantManageCategories::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$parent])
            ->assertCanNotSeeTableRecords([$child])
            ->call('toggleRowExpansion', (string) $parent->getKey())
            ->assertCanSeeTableRecords([$parent, $child]);
    }

    /**
     * @return array{0: ContentCategory, 1: ContentCategory} 根分类与子分类
     */
    private function makeCategories(Tenant $tenant): array
    {
        $parent = ContentCategory::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => '内容根分类',
            'sort' => 0,
        ]);

        $child = ContentCategory::factory()->childOf($parent)->create([
            'tenant_id' => $tenant->getKey(),
            'name' => '内容子分类',
            'sort' => 0,
        ]);

        return [$parent, $child];
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
