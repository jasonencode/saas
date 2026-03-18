<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\WechatMinis\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\WechatMinis\WechatMiniResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWechatMinis extends ManageRecords
{
    protected static string $resource = WechatMiniResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
