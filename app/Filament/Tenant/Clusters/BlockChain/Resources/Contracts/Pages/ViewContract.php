<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\Pages;

use App\Filament\Actions\BlockChain\ContractDeployAction;
use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\ContractResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
            ContractDeployAction::make(),
        ];
    }
}
