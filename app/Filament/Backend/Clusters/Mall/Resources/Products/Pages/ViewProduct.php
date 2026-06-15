<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\ProductAuditAction;
use App\Filament\Actions\Mall\ProductDownAction;
use App\Filament\Actions\Mall\ProductUpAction;
use App\Filament\Backend\Clusters\Mall\Resources\Products\ProductResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ProductUpAction::make(),
            ProductDownAction::make(),
            ProductAuditAction::make(),
        ];
    }

    public function getRecordTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }
}
