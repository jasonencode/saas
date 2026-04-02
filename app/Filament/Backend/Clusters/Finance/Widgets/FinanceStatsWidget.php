<?php

namespace App\Filament\Backend\Clusters\Finance\Widgets;

use App\Enums\PaymentRefundStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Filament\Backend\Clusters\Finance\Resources\Refunds\RefundResource;
use App\Models\PaymentOrder;
use App\Models\PaymentRefund;
use App\Models\Refund;
use App\Models\Tenant;
use App\Models\UserAccount;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', true)->count();

        $totalBalance = UserAccount::sum('balance');
        $totalFrozenBalance = UserAccount::sum('frozen_balance');
        $totalPoints = UserAccount::sum('points');

        $totalPaidAmount = PaymentOrder::where('status', PaymentStatus::Paid)->sum('amount');
        $pendingPaymentsCount = PaymentOrder::where('status', PaymentStatus::Pending)->count();

        $pendingRefundsCount = Refund::where('status', RefundStatus::Pending)->count();
        $processingRefundsCount = PaymentRefund::whereIn('status', [
            PaymentRefundStatus::Pending,
            PaymentRefundStatus::Approved,
            PaymentRefundStatus::Processing,
        ])->count();

        return [
            Stat::make('租户总数', $totalTenants)
                ->description('所有注册租户')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('info'),

            Stat::make('活跃租户', $activeTenants)
                ->description('状态正常的租户')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('平台总余额', '￥'.number_format((float) $totalBalance, 2))
                ->description('所有租户用户余额之和')
                ->descriptionIcon('heroicon-o-wallet')
                ->color('success'),

            Stat::make('冻结余额', '￥'.number_format((float) $totalFrozenBalance, 2))
                ->description('所有用户冻结余额之和')
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make('积分总量', number_format((float) $totalPoints, 2))
                ->description('所有租户用户积分之和')
                ->descriptionIcon('heroicon-o-star')
                ->color('amber'),

            Stat::make('已支付金额', '￥'.number_format((float) $totalPaidAmount, 2))
                ->description('已完成的支付总额')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make('待支付订单', $pendingPaymentsCount)
                ->description('等待用户支付的订单')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'),

            Stat::make('待处理售后', $pendingRefundsCount)
                ->description('等待处理的商城退款')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->url(RefundResource::getIndexUrl(['tab' => 'pending'])),

            Stat::make('财务退款处理中', $processingRefundsCount)
                ->description('正在审核/处理中的支付退款')
                ->descriptionIcon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->url(RefundResource::getIndexUrl()),
        ];
    }
}
