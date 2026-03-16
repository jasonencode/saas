<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\ContractResource;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
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
