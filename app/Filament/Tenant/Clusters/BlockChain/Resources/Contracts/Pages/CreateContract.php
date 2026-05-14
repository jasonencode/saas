<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\ContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            $this->getSubmitFormAction()
                ->formId('form'),
        ];
    }
}
