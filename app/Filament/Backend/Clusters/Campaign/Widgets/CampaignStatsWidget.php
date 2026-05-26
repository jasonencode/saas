<?php

namespace App\Filament\Backend\Clusters\Campaign\Widgets;

use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\RedpackCodeStatus;
use App\Filament\Backend\Clusters\Campaign\Resources\Coupons\CouponResource;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\RedpackResource;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponOrder;
use App\Models\Campaign\CouponUser;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::where('status', true)->count();

        $fixedCoupons = Coupon::where('type', CouponType::Fixed)->count();
        $percentCoupons = Coupon::where('type', CouponType::Percent)->count();

        $totalClaims = CouponUser::count();
        $usedCoupons = CouponUser::where('is_used', true)->count();

        $totalDiscount = CouponOrder::sum('discount_amount');

        $totalRedpacks = Redpack::count();
        $activeRedpacks = Redpack::where('status', true)->count();

        $totalCodes = RedpackCode::count();
        $claimedCodes = RedpackCode::where('status', RedpackCodeStatus::Claimed)->count();

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
                ->description('已领取：'.$claimedCodes.' / 待领取：'.($totalCodes - $claimedCodes))
                ->descriptionIcon(Heroicon::OutlinedQrCode)
                ->color('success')
                ->url(RedpackResource::getUrl()),

            Stat::make('今日新增优惠券', Coupon::whereDate('created_at', Carbon::today())->count())
                ->description('近7天：'.Coupon::whereDate('created_at', '>=', Carbon::today()->subDays(7))->count())
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(CouponResource::getUrl()),
        ];
    }
}
