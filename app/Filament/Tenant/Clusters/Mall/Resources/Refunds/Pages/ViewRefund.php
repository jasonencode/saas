<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\CancelRefundAction;
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
            ShipReturnAction::make(),
            CancelRefundAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
