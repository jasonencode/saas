<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAliyun extends ViewRecord
{
    protected static string $resource = AliyunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
            RefreshAction::make(),
        ];
    }
}
