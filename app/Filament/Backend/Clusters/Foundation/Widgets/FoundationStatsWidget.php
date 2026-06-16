<?php

namespace App\Filament\Backend\Clusters\Foundation\Widgets;

use App\Filament\Backend\Clusters\Foundation\Resources\Alipays\AlipayResource;
use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use App\Filament\Backend\Clusters\Foundation\Resources\Socialites\SocialitesResource;
use App\Filament\Backend\Clusters\Foundation\Resources\WechatMinis\WechatMiniResource;
use App\Filament\Backend\Clusters\Foundation\Resources\WechatPayments\WechatPaymentResource;
use App\Filament\Backend\Clusters\Foundation\Resources\Wechats\WechatResource;
use App\Models\Foundation\Alipay;
use App\Models\Foundation\Aliyun;
use App\Models\Foundation\Socialite;
use App\Models\Foundation\SocialiteAccount;
use App\Models\Foundation\Wechat;
use App\Models\Foundation\WechatMini;
use App\Models\Foundation\WechatPayment;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class FoundationStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $cacheKey = 'foundation_stats_widget';
        $cacheTtl = 60;

        $data = Cache::remember($cacheKey, $cacheTtl, static function () {
            return [
                'total_aliyun' => Aliyun::count(),
                'active_aliyun' => Aliyun::where('status', true)->count(),
                'total_wechat' => Wechat::count(),
                'active_wechat' => Wechat::where('status', true)->count(),
                'connected_wechat' => Wechat::where('is_connected', true)->count(),
                'total_payment' => WechatPayment::count(),
                'active_payment' => WechatPayment::where('status', true)->count(),
                'total_mini' => WechatMini::count(),
                'active_mini' => WechatMini::where('status', true)->count(),
                'total_alipay' => Alipay::count(),
                'active_alipay' => Alipay::where('status', true)->count(),
                'total_socialite' => Socialite::count(),
                'total_socialite_account' => SocialiteAccount::count(),
            ];
        });

        return [
            Stat::make('阿里云配置', $data['total_aliyun'])
                ->description("启用：{$data['active_aliyun']} / 停用：".($data['total_aliyun'] - $data['active_aliyun']))
                ->descriptionIcon(Heroicon::OutlinedCloudArrowUp)
                ->color('primary')
                ->url(AliyunResource::getUrl()),

            Stat::make('微信应用', $data['total_wechat'])
                ->description("启用：{$data['active_wechat']} / 已连接：{$data['connected_wechat']}")
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(WechatResource::getUrl()),

            Stat::make('微信支付', $data['total_payment'])
                ->description("启用：{$data['active_payment']} / 停用：".($data['total_payment'] - $data['active_payment']))
                ->descriptionIcon(Heroicon::OutlinedCurrencyYen)
                ->color('warning')
                ->url(WechatPaymentResource::getUrl()),

            Stat::make('微信小程序', $data['total_mini'])
                ->description("启用：{$data['active_mini']} / 停用：".($data['total_mini'] - $data['active_mini']))
                ->descriptionIcon(Heroicon::OutlinedDevicePhoneMobile)
                ->color('info')
                ->url(WechatMiniResource::getUrl()),

            Stat::make('支付宝配置', $data['total_alipay'])
                ->description("启用：{$data['active_alipay']} / 停用：".($data['total_alipay'] - $data['active_alipay']))
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('danger')
                ->url(AlipayResource::getUrl()),

            Stat::make('第三方登录', $data['total_socialite'])
                ->description("登录账号：{$data['total_socialite_account']}")
                ->descriptionIcon(Heroicon::OutlinedArrowRightOnRectangle)
                ->color('gray')
                ->url(SocialitesResource::getUrl()),
        ];
    }
}
