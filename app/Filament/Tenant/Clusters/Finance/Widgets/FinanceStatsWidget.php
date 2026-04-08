<?php

namespace App\Filament\Tenant\Clusters\Finance\Widgets;

use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\RefundStatus;
use App\Filament\Tenant\Clusters\Finance\Resources\Accounts\AccountResource;
use App\Filament\Tenant\Clusters\Finance\Resources\Payments\PaymentResource;
use App\Filament\Tenant\Clusters\Finance\Resources\Refunds\RefundResource;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Refund;
use App\Models\User\UserAccount;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalBalance = UserAccount::sum('balance');
        $totalFrozenBalance = UserAccount::sum('frozen_balance');
        $totalPoints = UserAccount::sum('points');

        $totalPaidAmount = PaymentOrder::where('status', PaymentStatus::Paid)->sum('amount');
        $pendingPaymentsCount = PaymentOrder::where('status', PaymentStatus::Pending)->count();
        $totalPaymentsCount = PaymentOrder::count();

        $pendingRefundsCount = Refund::where('status', RefundStatus::Pending)->count();
        $processingRefundsCount = PaymentRefund::whereIn('status', [
            PaymentRefundStatus::Pending,
            PaymentRefundStatus::Approved,
            PaymentRefundStatus::Processing,
        ])->count();

        return [
            Stat::make('账户总余额', '￥'.number_format((float) $totalBalance, 2))
                ->description('所有用户余额之和')
                ->descriptionIcon('heroicon-o-wallet')
                ->color('success')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('冻结余额', '￥'.number_format((float) $totalFrozenBalance, 2))
                ->description('所有用户冻结余额之和')
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color('warning')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('积分总量', number_format((float) $totalPoints, 2))
                ->description('所有用户积分之和')
                ->descriptionIcon('heroicon-o-star')
                ->color('amber')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('已支付金额', '￥'.number_format((float) $totalPaidAmount, 2))
                ->description('已完成的支付总额')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info')
                ->url(PaymentResource::getIndexUrl()),

            Stat::make('待支付订单', $pendingPaymentsCount)
                ->description('等待用户支付的订单')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger')
                ->url(PaymentResource::getIndexUrl(['tab' => 'pending'])),

            Stat::make('支付订单总数', $totalPaymentsCount)
                ->description('所有支付订单')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('gray')
                ->url(PaymentResource::getIndexUrl()),

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
