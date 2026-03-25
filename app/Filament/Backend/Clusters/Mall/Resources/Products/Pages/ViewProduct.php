<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\ProductAuditAction;
use App\Filament\Backend\Clusters\Mall\Resources\Products\ProductResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ProductAuditAction::make(),
        ];
    }
}
