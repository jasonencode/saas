<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Refunds\RefundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRefunds extends ListRecords
{
    protected static string $resource = RefundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
