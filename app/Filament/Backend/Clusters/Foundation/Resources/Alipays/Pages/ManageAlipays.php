<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Alipays\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\Alipays\AlipayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAlipays extends ManageRecords
{
    protected static string $resource = AlipayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
