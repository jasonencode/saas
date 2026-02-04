<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Refunds\RefundResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRefund extends ViewRecord
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
