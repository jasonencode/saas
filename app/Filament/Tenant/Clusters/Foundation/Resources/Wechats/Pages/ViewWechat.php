<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\WechatResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWechat extends ViewRecord
{
    protected static string $resource = WechatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
