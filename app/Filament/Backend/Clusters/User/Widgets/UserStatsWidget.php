<?php

namespace App\Filament\Backend\Clusters\User\Widgets;

use App\Enums\User\RealnameStatus;
use App\Filament\Backend\Clusters\User\Resources\Identities\IdentityResource;
use App\Filament\Backend\Clusters\User\Resources\Realnames\RealnameResource;
use App\Filament\Backend\Clusters\User\Resources\Tenants\TenantResource;
use App\Filament\Backend\Clusters\User\Resources\UserRelations\UserRelationResource;
use App\Filament\Backend\Clusters\User\Resources\Users\UserResource;
use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Models\User\UserRealname;
use App\Models\User\UserRelation;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $todayNewUsers = User::whereDate('created_at', today())->count();
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', true)->count();
        $approvedRealnames = UserRealname::where('status', RealnameStatus::Approved)->count();
        $pendingRealnames = UserRealname::where('status', RealnameStatus::Pending)->count();
        $totalIdentities = Identity::count();
        $activeIdentities = Identity::where('status', true)->count();
        $relationNodes = UserRelation::count();
        $deepestLayer = UserRelation::max('layer') ?? 0;

        $tenantUsers = User::whereNotNull('tenant_id')->count();
        $avgUsersPerTenant = $activeTenants > 0
            ? number_format(round($tenantUsers / $activeTenants, 1), 1)
            : '0';

        return [
            Stat::make('用户总数', number_format($totalUsers))
                ->description('所有注册用户')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('info')
                ->url(UserResource::getIndexUrl()),

            Stat::make('今日新增', number_format($todayNewUsers))
                ->description('今日注册用户数')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->color('success')
                ->url(UserResource::getIndexUrl()),

            Stat::make('租户总数', $totalTenants)
                ->description("活跃 {$activeTenants} / 总 {$totalTenants}")
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice)
                ->color('gray')
                ->url(TenantResource::getIndexUrl()),

            Stat::make('租户用户', number_format($tenantUsers))
                ->description("均 {$avgUsersPerTenant} / 户")
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('indigo')
                ->url(UserResource::getIndexUrl()),

            Stat::make('实名认证', number_format($approvedRealnames))
                ->description("待审核 {$pendingRealnames} 个")
                ->descriptionIcon(Heroicon::OutlinedIdentification)
                ->color('success')
                ->url(RealnameResource::getIndexUrl()),

            Stat::make('身份管理', $totalIdentities)
                ->description("启用 {$activeIdentities} 个")
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('warning')
                ->url(IdentityResource::getIndexUrl()),

            Stat::make('推荐关系', number_format($relationNodes))
                ->description("最大层级 {$deepestLayer} 层")
                ->descriptionIcon(Heroicon::OutlinedShare)
                ->color('amber')
                ->url(UserRelationResource::getIndexUrl()),
        ];
    }
}
