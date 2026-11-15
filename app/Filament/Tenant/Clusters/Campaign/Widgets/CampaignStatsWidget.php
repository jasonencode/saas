<?php

namespace App\Filament\Tenant\Clusters\Campaign\Widgets;

use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\RedpackCodeStatus;
use App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\CouponResource;
use App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\RedpackResource;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponOrder;
use App\Models\Campaign\CouponUser;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class CampaignStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()?->getKey();

        // CouponUser / CouponOrder / RedpackCode 无租户列，统一经 coupon/redpack 关联
        // 限定当前租户，避免统计混入全平台数据（CAMP-08）
        $ofTenant = fn (Builder $query): Builder => $query->where('tenant_id', $tenantId);

        $claims = CouponUser::query()->whereHas('coupon', $ofTenant);
        $totalClaims = (clone $claims)->count();
        $usedCoupons = (clone $claims)->where('is_used', true)->count();

        $codes = RedpackCode::query()->whereHas('redpack', $ofTenant);
        $totalCodes = (clone $codes)->count();
        // 已领取含领取中 / 已发放，不再只统计 Claimed（CAMP-08）
        $claimedCodes = (clone $codes)->whereIn('status', [
            RedpackCodeStatus::Claimed->value,
            RedpackCodeStatus::Sending->value,
            RedpackCodeStatus::Sent->value,
        ])->count();
        $activeCodes = (clone $codes)->where('status', RedpackCodeStatus::Active->value)->count();

        $totalCoupons = Coupon::query()->where('tenant_id', $tenantId)->count();
        $activeCoupons = Coupon::query()->where('tenant_id', $tenantId)->where('status', true)->count();
        $fixedCoupons = Coupon::query()->where('tenant_id', $tenantId)->where('type', CouponType::Fixed)->count();
        $percentCoupons = Coupon::query()->where('tenant_id', $tenantId)->where('type', CouponType::Percent)->count();
        $totalDiscount = CouponOrder::query()->whereHas('coupon', $ofTenant)->sum('discount_amount');

        $totalRedpacks = Redpack::query()->where('tenant_id', $tenantId)->count();
        $activeRedpacks = Redpack::query()->where('tenant_id', $tenantId)->ofActive()->count();

        return [
            Stat::make('优惠券总数', $totalCoupons)
                ->description('启用：'.$activeCoupons.' / 停用：'.($totalCoupons - $activeCoupons))
                ->descriptionIcon(Heroicon::OutlinedViewColumns)
                ->color('primary')
                ->url(CouponResource::getUrl()),

            Stat::make('优惠券类型', '固定 '.$fixedCoupons.' / 百分比 '.$percentCoupons)
                ->description('固定金额与百分比折扣券分布')
                ->descriptionIcon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->color('info')
                ->url(CouponResource::getUrl()),

            Stat::make('领券用户', $totalClaims)
                ->description('已使用：'.$usedCoupons.' / 未使用：'.($totalClaims - $usedCoupons))
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success')
                ->url(CouponResource::getUrl()),

            Stat::make('优惠券抵扣', '￥'.number_format((float) $totalDiscount, 2))
                ->description('所有订单优惠券抵扣总金额')
                ->descriptionIcon(Heroicon::OutlinedCurrencyDollar)
                ->color('warning')
                ->url(CouponResource::getUrl()),

            Stat::make('红包活动', $totalRedpacks)
                ->description('进行中：'.$activeRedpacks.' / 已结束：'.($totalRedpacks - $activeRedpacks))
                ->descriptionIcon(Heroicon::OutlinedEnvelopeOpen)
                ->color('info')
                ->url(RedpackResource::getUrl()),

            Stat::make('红包码', $totalCodes)
                ->description('已领取：'.$claimedCodes.' / 待领取：'.$activeCodes)
                ->descriptionIcon(Heroicon::OutlinedQrCode)
                ->color('success')
                ->url(RedpackResource::getUrl()),

            Stat::make('今日新增优惠券', Coupon::query()->where('tenant_id', $tenantId)->whereDate('created_at', Carbon::today())->count())
                ->description('近7天：'.Coupon::query()->where('tenant_id', $tenantId)->whereDate('created_at', '>=', Carbon::today()->subDays(7))->count())
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(CouponResource::getUrl()),
        ];
    }
}
