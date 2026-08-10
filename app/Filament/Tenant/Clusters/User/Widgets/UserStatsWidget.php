<?php

namespace App\Filament\Tenant\Clusters\User\Widgets;

use App\Enums\User\RealnameStatus;
use App\Filament\Tenant\Clusters\User\Resources\Identities\IdentityResource;
use App\Filament\Tenant\Clusters\User\Resources\UserRealnames\UserRealnameResource;
use App\Filament\Tenant\Clusters\User\Resources\Users\UserResource;
use App\Filament\Tenant\Clusters\User\UserCluster;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return UserCluster::canAccess();
    }

    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()?->getKey();

        $userIds = User::withoutGlobalScopes()
            ->select('id')
            ->whereIn('id', fn ($q) => $q->select('user_id')->from('user_tenant')->where('tenant_id', $tenantId))
            ->pluck('id');

        $totalUsers = $userIds->count();

        $todayNewUsers = User::withoutGlobalScopes()
            ->whereIn('id', $userIds)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $realnameStats = UserRealname::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->selectRaw('
                COUNT(CASE WHEN status = ? THEN 1 END) as approved,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending
            ', [RealnameStatus::Approved->value, RealnameStatus::Pending->value])
            ->first();

        $approvedRealnames = (int) ($realnameStats->approved ?? 0);
        $pendingRealnames = (int) ($realnameStats->pending ?? 0);

        $identityStats = Identity::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = ? THEN 1 END) as active
            ', [true])
            ->first();

        $totalIdentities = (int) ($identityStats->total ?? 0);
        $activeIdentities = (int) ($identityStats->active ?? 0);

        return [
            Stat::make('用户总数', number_format($totalUsers))
                ->description('所有用户')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('primary')
                ->url(UserResource::getIndexUrl()),

            Stat::make('今日新增', number_format($todayNewUsers))
                ->description('今日注册用户数')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->color('success')
                ->url(UserResource::getIndexUrl()),

            Stat::make('实名认证', number_format($approvedRealnames))
                ->description('待审核 '.$pendingRealnames.' 个')
                ->descriptionIcon(Heroicon::OutlinedIdentification)
                ->color('info')
                ->url(UserRealnameResource::getIndexUrl()),

            Stat::make('身份管理', $totalIdentities)
                ->description('启用 '.$activeIdentities.' 个')
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('warning')
                ->url(IdentityResource::getIndexUrl()),
        ];
    }
}
