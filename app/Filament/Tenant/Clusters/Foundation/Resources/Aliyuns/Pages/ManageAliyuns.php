<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Aliyuns\Pages;

use App\Filament\Tenant\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAliyuns extends ManageRecords
{
    protected static string $resource = AliyunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
