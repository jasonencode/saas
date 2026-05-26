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

class FoundationStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // 阿里云
        $totalAliyun = Aliyun::count();
        $activeAliyun = Aliyun::where('status', true)->count();

        // 微信应用
        $totalWechat = Wechat::count();
        $activeWechat = Wechat::where('status', true)->count();
        $connectedWechat = Wechat::where('is_connected', true)->count();

        // 微信支付
        $totalPayment = WechatPayment::count();
        $activePayment = WechatPayment::where('status', true)->count();

        // 微信小程序
        $totalMini = WechatMini::count();
        $activeMini = WechatMini::where('status', true)->count();

        // 支付宝
        $totalAlipay = Alipay::count();
        $activeAlipay = Alipay::where('status', true)->count();

        // 第三方登录
        $totalSocialite = Socialite::count();
        $totalSocialiteAccount = SocialiteAccount::count();

        return [
            Stat::make('阿里云配置', $totalAliyun)
                ->description('启用：'.$activeAliyun.' / 停用：'.($totalAliyun - $activeAliyun))
                ->descriptionIcon(Heroicon::OutlinedCloudArrowUp)
                ->color('primary')
                ->url(AliyunResource::getUrl()),

            Stat::make('微信应用', $totalWechat)
                ->description('启用：'.$activeWechat.' / 已连接：'.$connectedWechat)
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(WechatResource::getUrl()),

            Stat::make('微信支付', $totalPayment)
                ->description('启用：'.$activePayment.' / 停用：'.($totalPayment - $activePayment))
                ->descriptionIcon(Heroicon::OutlinedCurrencyYen)
                ->color('warning')
                ->url(WechatPaymentResource::getUrl()),

            Stat::make('微信小程序', $totalMini)
                ->description('启用：'.$activeMini.' / 停用：'.($totalMini - $activeMini))
                ->descriptionIcon(Heroicon::OutlinedDevicePhoneMobile)
                ->color('info')
                ->url(WechatMiniResource::getUrl()),

            Stat::make('支付宝配置', $totalAlipay)
                ->description('启用：'.$activeAlipay.' / 停用：'.($totalAlipay - $activeAlipay))
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('danger')
                ->url(AlipayResource::getUrl()),

            Stat::make('第三方登录', $totalSocialite)
                ->description('登录账号：'.$totalSocialiteAccount)
                ->descriptionIcon(Heroicon::OutlinedArrowRightOnRectangle)
                ->color('gray')
                ->url(SocialitesResource::getUrl()),
        ];
    }
}
