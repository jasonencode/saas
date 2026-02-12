<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\BlockChain\Resources\Networks\NetworkResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNetwork extends ViewRecord
{
    protected static string $resource = NetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}
