<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Refunds\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Finance\ApprovePaymentRefundAction;
use App\Filament\Actions\Finance\ExecutePaymentRefundAction;
use App\Filament\Actions\Finance\RejectPaymentRefundAction;
use App\Filament\Actions\Finance\RetryPaymentRefundAction;
use App\Filament\Backend\Clusters\Finance\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRefund extends ViewRecord
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ApprovePaymentRefundAction::make(),
            RejectPaymentRefundAction::make(),
            ExecutePaymentRefundAction::make(),
            RetryPaymentRefundAction::make(),
            BackAction::make(),
        ];
    }
}
