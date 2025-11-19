<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\ApiLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewApiLog extends ViewRecord
{
    protected static string $resource = ApiLogResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('返回列表')
                ->icon(Heroicon::ArrowLeft)
                ->url(self::$resource::getUrl()),
        ];
    }
}
