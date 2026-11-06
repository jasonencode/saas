<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\SeckillActivities\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Campaign\Resources\SeckillActivities\SeckillActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSeckillActivity extends ViewRecord
{
    protected static string $resource = SeckillActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            Actions\EditAction::make(),
        ];
    }
}
