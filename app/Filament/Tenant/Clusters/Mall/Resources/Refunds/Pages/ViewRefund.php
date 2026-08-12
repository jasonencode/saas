<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\ApproveRefundAction;
use App\Filament\Actions\Mall\CancelRefundAction;
use App\Filament\Actions\Mall\ConfirmReceiveAction;
use App\Filament\Actions\Mall\ConfirmRefundAction;
use App\Filament\Actions\Mall\RejectRefundAction;
use App\Filament\Actions\Mall\ShipReturnAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRefund extends ViewRecord
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ApproveRefundAction::make(),
            RejectRefundAction::make(),
            ShipReturnAction::make(),
            ConfirmReceiveAction::make(),
            ConfirmRefundAction::make(),
            CancelRefundAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
