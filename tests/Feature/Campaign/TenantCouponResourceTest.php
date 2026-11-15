<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\Pages\ManageCoupons;
use App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\Pages\ViewCoupon;
use App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\RelationManagers\UsersRelationManager;
use App\Filament\Tenant\Clusters\Campaign\Widgets\CampaignStatsWidget;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Campaign\Redpack;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class TenantCouponResourceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'config' => ['modules' => [AvailableModule::Campaign->value]],
        ]);

        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($this->tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($this->tenant, isQuiet: true);
        Gate::before(fn (): bool => true);
    }

    public function test_coupon_pages_render(): void
    {
        $coupon = Coupon::factory()->create(['tenant_id' => $this->tenant->getKey()]);

        Livewire::test(ManageCoupons::class)->assertOk();
        Livewire::test(ViewCoupon::class, ['record' => $coupon->getKey()])->assertOk();
    }

    public function test_used_coupon_holding_cannot_be_selected_or_detached(): void
    {
        $coupon = Coupon::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $this->userWithCoupon($coupon, isUsed: true);
        $this->userWithCoupon($coupon, isUsed: false);

        $table = Livewire::test(UsersRelationManager::class, [
            'ownerRecord' => $coupon,
            'pageClass' => ViewCoupon::class,
        ])->instance()->getTable();

        $records = $coupon->users()->get()->keyBy('id');

        // 已核销的持有记录不可选中（批量解除被拦截），未核销的可选中（CAMP-09）
        $this->assertFalse($table->isRecordSelectable($records->first(fn ($user) => $user->pivot->is_used)));
        $this->assertTrue($table->isRecordSelectable($records->first(fn ($user) => !$user->pivot->is_used)));
    }

    public function test_search_users_only_returns_current_tenant_users(): void
    {
        $coupon = Coupon::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $mine = User::factory()->create(['username' => 'zhangsan']);
        $mine->tenants()->attach($this->tenant);
        $other = User::factory()->create(['username' => 'zhangsanfeng']);
        $other->tenants()->attach(Tenant::factory()->create());

        $relationManager = Livewire::test(UsersRelationManager::class, [
            'ownerRecord' => $coupon,
            'pageClass' => ViewCoupon::class,
        ])->instance();

        $results = (new ReflectionMethod(UsersRelationManager::class, 'searchUsers'))
            ->invoke($relationManager, 'zhang');

        // 发券搜索仅返回本租户用户，防止跨租户发券（CAMP-09）
        $this->assertSame([$mine->getKey() => 'zhangsan'], $results);
    }

    public function test_stats_widget_only_counts_current_tenant(): void
    {
        $mine = Coupon::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        Coupon::factory()->count(2)->create(['tenant_id' => Tenant::factory()->create()->getKey()]);

        $this->userWithCoupon($mine, isUsed: true);

        $redpack = Redpack::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $redpack->codes()->create(['amount' => 1, 'status' => RedpackCodeStatus::Claimed, 'user_id' => User::factory()->create()->getKey()]);
        $redpack->codes()->create(['amount' => 1, 'status' => RedpackCodeStatus::Sending, 'user_id' => User::factory()->create()->getKey()]);
        $redpack->codes()->create(['amount' => 1, 'status' => RedpackCodeStatus::Active]);

        $otherRedpack = Redpack::factory()->create(['tenant_id' => Tenant::factory()->create()->getKey()]);
        $otherRedpack->codes()->create(['amount' => 1, 'status' => RedpackCodeStatus::Claimed, 'user_id' => User::factory()->create()->getKey()]);

        $stats = (new ReflectionMethod(CampaignStatsWidget::class, 'getStats'))
            ->invoke(new CampaignStatsWidget);

        // 0 优惠券总数 / 2 领券用户 / 4 红包活动 / 5 红包码（CAMP-08）
        $this->assertSame(1, $stats[0]->getValue());
        $this->assertSame(1, $stats[2]->getValue());
        $this->assertSame(1, $stats[4]->getValue());
        // 已领取含 claimed + sending，不把 sending 算进待领取
        $this->assertSame(3, $stats[5]->getValue());
    }

    private function userWithCoupon(Coupon $coupon, bool $isUsed): User
    {
        $user = User::factory()->create();
        $user->tenants()->attach($this->tenant);

        CouponUser::query()->create([
            'coupon_id' => $coupon->getKey(),
            'user_id' => $user->getKey(),
            'is_used' => $isUsed,
            'used_at' => $isUsed ? now() : null,
            'expired_at' => now()->addDay(),
        ]);

        return $user;
    }
}
