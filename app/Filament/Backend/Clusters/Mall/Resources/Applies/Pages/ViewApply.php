<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Applies\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\StoreApplyAuditAction;
use App\Filament\Backend\Clusters\Mall\Resources\Applies\ApplyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewApply extends ViewRecord
{
    protected static string $resource = ApplyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            StoreApplyAuditAction::make(),
        ];
    }
}
