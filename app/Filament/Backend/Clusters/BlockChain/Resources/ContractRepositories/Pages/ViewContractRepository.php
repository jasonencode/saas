<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\ContractRepositoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContractRepository extends ViewRecord
{
    protected static string $resource = ContractRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}
