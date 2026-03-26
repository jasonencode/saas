<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Refunds\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Finance\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRefund extends ViewRecord
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
