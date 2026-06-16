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
use Illuminate\Support\Facades\Cache;

class CampaignStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $cacheKey = 'campaign_stats_widget';
        $cacheTtl = 60;

        $data = Cache::remember($cacheKey, $cacheTtl, static function () {
            $today = Carbon::today();
            $weekAgo = $today->copy()->subDays(7);

            $totalCoupons = Coupon::count();
            $activeCoupons = Coupon::where('status', true)->count();
            $fixedCoupons = Coupon::where('type', CouponType::Fixed)->count();
            $percentCoupons = Coupon::where('type', CouponType::Percent)->count();
            $totalClaims = CouponUser::count();
            $usedCoupons = CouponUser::where('is_used', true)->count();
            $totalDiscount = CouponOrder::sum('discount_amount');
            $totalRedpacks = Redpack::count();
            $activeRedpacks = Redpack::ofActive()->count();
            $totalCodes = RedpackCode::count();
            $claimedCodes = RedpackCode::where('status', RedpackCodeStatus::Claimed)->count();
            $todayCoupons = Coupon::whereDate('created_at', $today)->count();
            $weekCoupons = Coupon::whereDate('created_at', '>=', $weekAgo)->count();

            return compact(
                'totalCoupons', 'activeCoupons', 'fixedCoupons', 'percentCoupons',
                'totalClaims', 'usedCoupons', 'totalDiscount', 'totalRedpacks',
                'activeRedpacks', 'totalCodes', 'claimedCodes', 'todayCoupons', 'weekCoupons'
            );
        });

        return [
            Stat::make('优惠券总数', $data['totalCoupons'])
                ->description("启用：{$data['activeCoupons']} / 停用：".($data['totalCoupons'] - $data['activeCoupons']))
                ->descriptionIcon(Heroicon::OutlinedViewColumns)
                ->color('primary')
                ->url(CouponResource::getUrl()),

            Stat::make('优惠券类型', "固定 {$data['fixedCoupons']} / 百分比 {$data['percentCoupons']}")
                ->description('固定金额与百分比折扣券分布')
                ->descriptionIcon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->color('info')
                ->url(CouponResource::getUrl()),

            Stat::make('领券用户', $data['totalClaims'])
                ->description("已使用：{$data['usedCoupons']} / 未使用：".($data['totalClaims'] - $data['usedCoupons']))
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success')
                ->url(CouponResource::getUrl()),

            Stat::make('优惠券抵扣', '￥'.number_format((float) $data['totalDiscount'], 2))
                ->description('所有订单优惠券抵扣总金额')
                ->descriptionIcon(Heroicon::OutlinedCurrencyDollar)
                ->color('warning')
                ->url(CouponResource::getUrl()),

            Stat::make('红包活动', $data['totalRedpacks'])
                ->description("进行中：{$data['activeRedpacks']} / 已结束：".($data['totalRedpacks'] - $data['activeRedpacks']))
                ->descriptionIcon(Heroicon::OutlinedEnvelopeOpen)
                ->color('info')
                ->url(RedpackResource::getUrl()),

            Stat::make('红包码', $data['totalCodes'])
                ->description("已领取：{$data['claimedCodes']} / 待领取：".($data['totalCodes'] - $data['claimedCodes']))
                ->descriptionIcon(Heroicon::OutlinedQrCode)
                ->color('success')
                ->url(RedpackResource::getUrl()),

            Stat::make('今日新增优惠券', $data['todayCoupons'])
                ->description("近7天：{$data['weekCoupons']}")
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(CouponResource::getUrl()),
        ];
    }
}
