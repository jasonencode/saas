<?php

namespace App\Filament\Tenant\Clusters\Foundation\Widgets;

use App\Filament\Tenant\Clusters\Foundation\Resources\Alipays\AlipayResource;
use App\Filament\Tenant\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use App\Filament\Tenant\Clusters\Foundation\Resources\Socialites\SocialitesResource;
use App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\WechatResource;
use App\Models\Foundation\Alipay;
use App\Models\Foundation\Aliyun;
use App\Models\Foundation\AliyunDns;
use App\Models\Foundation\AliyunDomain;
use App\Models\Foundation\AliyunEcs;
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
        return [
            Stat::make('微信配置', Wechat::count())
                ->description('微信支付：'.WechatPayment::count().' / 小程序：'.WechatMini::count())
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success')
                ->url(WechatResource::getIndexUrl()),

            Stat::make('支付宝配置', Alipay::count())
                ->description('支付渠道配置')
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('info')
                ->url(AlipayResource::getIndexUrl()),

            Stat::make('社交登录', Socialite::count())
                ->description('绑定账号：'.SocialiteAccount::count())
                ->descriptionIcon(Heroicon::OutlinedKey)
                ->color('warning')
                ->url(SocialitesResource::getIndexUrl()),

            Stat::make('阿里云账号', Aliyun::count())
                ->descriptionIcon(Heroicon::OutlinedCloud)
                ->color('gray')
                ->url(AliyunResource::getIndexUrl()),
        ];
    }
}
