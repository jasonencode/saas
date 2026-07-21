<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\HeaderSubmitAction;
use App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\ContractRepositoryResource;
use Filament\Resources\Pages\EditRecord;

class EditContractRepository extends EditRecord
{
    protected static string $resource = ContractRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            HeaderSubmitAction::make(),
        ];
    }
}
