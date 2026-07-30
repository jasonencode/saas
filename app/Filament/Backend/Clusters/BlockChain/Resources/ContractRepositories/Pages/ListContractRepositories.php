<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Pages;

use App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\ContractRepositoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractRepositories extends ListRecords
{
    protected static string $resource = ContractRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
