<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\StoreApplies\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\StoreApplyAuditAction;
use App\Filament\Backend\Clusters\Mall\Resources\StoreApplies\StoreApplyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreApply extends ViewRecord
{
    protected static string $resource = StoreApplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            StoreApplyAuditAction::make(),
        ];
    }
}
