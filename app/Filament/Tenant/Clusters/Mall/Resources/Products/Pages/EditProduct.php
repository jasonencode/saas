<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            $this->getSubmitFormAction()
                ->formId('form'),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}

