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
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice)
                ->color('info')
                ->url(TenantResource::getIndexUrl()),

            Stat::make('活跃租户', $activeTenants)
                ->description('状态正常的租户')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->url(TenantResource::getIndexUrl()),

            Stat::make('平台总余额', '￥'.number_format((float) $totalBalance, 2))
                ->description('所有租户用户余额之和')
                ->descriptionIcon(Heroicon::OutlinedWallet)
                ->color('success')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('冻结余额', '￥'.number_format((float) $totalFrozenBalance, 2))
                ->description('所有用户冻结余额之和')
                ->descriptionIcon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('积分总量', number_format((float) $totalPoints, 2))
                ->description('所有租户用户积分之和')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->color('amber')
                ->url(AccountResource::getIndexUrl()),

            Stat::make('已支付金额', '￥'.number_format((float) $totalPaidAmount, 2))
                ->description('已完成的支付总额')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('info')
                ->url(PaymentResource::getIndexUrl()),

            Stat::make('待支付订单', $pendingPaymentsCount)
                ->description('等待用户支付的订单')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('danger')
                ->url(PaymentResource::getIndexUrl(['tab' => 'pending'])),

            Stat::make('待处理售后', $pendingRefundsCount)
                ->description('等待处理的商城退款')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('danger')
                ->url(RefundResource::getIndexUrl(['tab' => 'pending'])),

            Stat::make('财务退款处理中', $processingRefundsCount)
                ->description('正在审核/处理中的支付退款')
                ->descriptionIcon(Heroicon::OutlinedArrowUturnLeft)
                ->color('warning')
                ->url(RefundResource::getIndexUrl()),
        ];
    }
}
