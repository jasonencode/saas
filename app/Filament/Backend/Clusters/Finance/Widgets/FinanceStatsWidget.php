<?php

namespace App\Filament\Backend\Clusters\Finance\Widgets;

use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\RefundStatus;
use App\Filament\Backend\Clusters\Finance\Resources\Accounts\AccountResource;
use App\Filament\Backend\Clusters\Finance\Resources\Payments\PaymentResource;
use App\Filament\Backend\Clusters\Finance\Resources\Refunds\RefundResource;
use App\Filament\Backend\Clusters\User\Resources\Tenants\TenantResource;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Refund;
use App\Models\System\Tenant;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $cacheKey = 'finance_stats_widget';
        $cacheTtl = 60;

        $data = Cache::remember($cacheKey, $cacheTtl, function () {
            return [
                'total_tenants' => Tenant::count(),
                'active_tenants' => Tenant::where('status', true)->count(),
                'total_balance' => UserAccount::sum('balance'),
                'total_frozen_balance' => UserAccount::sum('frozen_balance'),
                'total_points' => UserAccount::sum('points'),
                'total_paid_amount' => PaymentOrder::where('status', PaymentStatus::Paid)->sum('amount'),
                'pending_payments_count' => PaymentOrder::where('status', PaymentStatus::Pending)->count(),
                'pending_refunds_count' => Refund::where('status', RefundStatus::Pending)->count(),
                'processing_refunds_count' => PaymentRefund::whereIn('status', [
                    PaymentRefundStatus::Pending,
                    PaymentRefundStatus::Approved,
                    PaymentRefundStatus::Processing,
                ])->count(),
            ];
        });

        return [
            Stat::make('租户总数', $data['total_tenants'])
                ->description('所有注册租户')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice)
                ->color('info')
                ->url(TenantResource::getIndexUrl()),

            Stat::make('活跃租户', $data['active_tenants'])
                ->description('状态正常的租户')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->url(TenantResource::getIndexUrl()),

            Stat::make('平台总余额', '￥'.number_format((float) $data['total_balance'], 2))
                ->description('所有租户用户余额之和')
                ->descriptionIcon(Heroicon::OutlinedWallet)
                ->color('success')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('冻结余额', '￥'.number_format((float) $data['total_frozen_balance'], 2))
                ->description('所有用户冻结余额之和')
                ->descriptionIcon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('积分总量', number_format((float) $data['total_points'], 2))
                ->description('所有租户用户积分之和')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->color('amber')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('已支付金额', '￥'.number_format((float) $data['total_paid_amount'], 2))
                ->description('已完成的支付总额')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('info')
                ->url(PaymentResource::getIndexUrl()),

            Stat::make('待支付订单', $data['pending_payments_count'])
                ->description('等待用户支付的订单')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('danger')
                ->url(PaymentResource::getIndexUrl(['tab' => 'pending'])),

            Stat::make('待处理售后', $data['pending_refunds_count'])
                ->description('等待处理的商城退款')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('danger')
                ->url(RefundResource::getIndexUrl(['tab' => 'pending'])),

            Stat::make('财务退款处理中', $data['processing_refunds_count'])
                ->description('正在审核/处理中的支付退款')
                ->descriptionIcon(Heroicon::OutlinedArrowUturnLeft)
                ->color('warning')
                ->url(RefundResource::getIndexUrl()),
        ];
    }
}
