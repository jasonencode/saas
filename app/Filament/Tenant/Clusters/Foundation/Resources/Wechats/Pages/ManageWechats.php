<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\Pages;

use App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\WechatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWechats extends ManageRecords
{
    protected static string $resource = WechatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
