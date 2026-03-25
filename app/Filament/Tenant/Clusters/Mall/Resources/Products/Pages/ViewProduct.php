<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\ProductDownAction;
use App\Filament\Actions\Mall\ProductUpAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ProductUpAction::make(),
            ProductDownAction::make(),
            EditAction::make(),
        ];
    }
}
