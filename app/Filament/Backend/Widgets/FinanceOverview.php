<?php

namespace App\Filament\Backend\Widgets;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\InvoiceApplication;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\Voucher;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;

class FinanceOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            StatsOverviewWidget\Stat::make('支付单数', PaymentOrder::count())
                ->description('今日支付：'.PaymentOrder::whereDate('created_at', Carbon::today())
                    ->where('status', PaymentStatus::Paid)->count())
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('success'),

            StatsOverviewWidget\Stat::make('待处理结算', Voucher::where('status', VoucherStatus::Pending)->count())
                ->description('待结算的凭据')
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('warning'),

            StatsOverviewWidget\Stat::make('待开发票', InvoiceApplication::where('status', InvoiceApplicationStatus::Pending)->count())
                ->description('待处理的发票申请')
                ->descriptionIcon(Heroicon::OutlinedReceiptPercent)
                ->color('danger'),
        ];
    }
}
