<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\ContractRepositoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContractRepository extends CreateRecord
{
    protected static string $resource = ContractRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            $this->getSubmitFormAction()
                ->formId('form'),
        ];
    }
}
