<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\SeckillActivities\Pages;

use App\Filament\Tenant\Clusters\Campaign\Resources\SeckillActivities\SeckillActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSeckillActivities extends ManageRecords
{
    protected static string $resource = SeckillActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
