<?php

namespace Tests\Feature\Campaign;

use App\Enums\System\AvailableModule;
use App\Filament\Backend\Clusters\Campaign\Resources\SeckillActivities\Pages\ManageSeckillActivities as BackendManageActivities;
use App\Filament\Backend\Clusters\Campaign\Resources\SeckillActivities\Pages\ViewSeckillActivity as BackendViewActivity;
use App\Filament\RelationManagers\Campaign\SeckillSessionsRelationManager;
use App\Filament\Tenant\Clusters\Campaign\Resources\SeckillActivities\Pages\ManageSeckillActivities as TenantManageActivities;
use App\Filament\Tenant\Clusters\Campaign\Resources\SeckillActivities\Pages\ViewSeckillActivity as TenantViewActivity;
use App\Models\Campaign\SeckillActivity;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class SeckillResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_seckill_pages_render(): void
    {
        $tenant = Tenant::factory()->create();
        $activity = $this->activity($tenant);

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');

        Livewire::test(BackendManageActivities::class)->assertOk();
        Livewire::test(BackendViewActivity::class, ['record' => $activity->getKey()])
            ->assertOk()
            ->assertSee('fi-wi-chart');
    }

    public function test_session_can_be_created_from_backend_activity_relation(): void
    {
        $tenant = Tenant::factory()->create();
        $activity = $this->activity($tenant);
        [$product, $sku] = $this->productWithSku($tenant);

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
        Gate::before(fn (): bool => true);

        Livewire::test(SeckillSessionsRelationManager::class, [
            'ownerRecord' => $activity,
            'pageClass' => BackendViewActivity::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: $this->sessionData($product, $sku))
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('seckill_sessions', [
            'tenant_id' => $tenant->getKey(),
            'activity_id' => $activity->getKey(),
            'name' => '10:00 场',
            'status' => 'scheduled',
        ]);
    }

    public function test_session_can_be_created_from_tenant_activity_relation(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => ['modules' => [AvailableModule::Campaign->value]],
        ]);
        $activity = $this->activity($tenant);
        [$product, $sku] = $this->productWithSku($tenant);
        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
        Gate::before(fn (): bool => true);

        Livewire::test(TenantManageActivities::class)->assertOk();
        Livewire::test(TenantViewActivity::class, ['record' => $activity->getKey()])
            ->assertOk()
            ->assertSee('fi-wi-chart');

        Livewire::test(SeckillSessionsRelationManager::class, [
            'ownerRecord' => $activity,
            'pageClass' => TenantViewActivity::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: $this->sessionData($product, $sku))
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('seckill_sessions', [
            'tenant_id' => $tenant->getKey(),
            'activity_id' => $activity->getKey(),
            'name' => '10:00 场',
            'status' => 'scheduled',
        ]);
    }

    public function test_session_creation_requires_future_start_time(): void
    {
        $tenant = Tenant::factory()->create();
        $activity = $this->activity($tenant);
        [$product, $sku] = $this->productWithSku($tenant);

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
        Gate::before(fn (): bool => true);

        Livewire::test(SeckillSessionsRelationManager::class, [
            'ownerRecord' => $activity,
            'pageClass' => BackendViewActivity::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                ...$this->sessionData($product, $sku),
                'start_at' => now()->subHour()->toDateTimeString(),
                'end_at' => now()->subMinutes(30)->toDateTimeString(),
            ])
            ->assertHasActionErrors(['start_at']);

        $this->assertDatabaseCount('seckill_sessions', 0);
    }

    public function test_session_creation_requires_end_time_after_start_time(): void
    {
        $tenant = Tenant::factory()->create();
        $activity = $this->activity($tenant);
        [$product, $sku] = $this->productWithSku($tenant);
        $time = now()->addHour()->toDateTimeString();

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
        Gate::before(fn (): bool => true);

        Livewire::test(SeckillSessionsRelationManager::class, [
            'ownerRecord' => $activity,
            'pageClass' => BackendViewActivity::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                ...$this->sessionData($product, $sku),
                'start_at' => $time,
                'end_at' => $time,
            ])
            ->assertHasActionErrors(['end_at']);

        $this->assertDatabaseCount('seckill_sessions', 0);
    }

    private function activity(Tenant $tenant): SeckillActivity
    {
        return SeckillActivity::create([
            'tenant_id' => $tenant->getKey(),
            'name' => '测试秒杀',
            'status' => 'published',
        ]);
    }

    /**
     * @return array{0: Product, 1: Sku}
     */
    private function productWithSku(Tenant $tenant): array
    {
        $product = Product::factory()->create(['tenant_id' => $tenant->getKey()]);

        $sku = Sku::factory()->create([
            'product_id' => $product->getKey(),
            'price' => 100,
            'stock' => 10,
        ]);

        return [$product, $sku];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionData(Product $product, Sku $sku): array
    {
        return [
            'name' => '10:00 场',
            'product_id' => $product->getKey(),
            'sku_id' => $sku->getKey(),
            'seckill_price' => 50,
            'stock' => 5,
            'limit_per_user' => 1,
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at' => now()->addHours(2)->toDateTimeString(),
            'status' => 'running',
        ];
    }
}
