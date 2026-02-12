<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Pages;

use App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\ContractResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
