<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAliyuns extends ListRecords
{
    protected static string $resource = AliyunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
