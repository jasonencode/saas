<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Certificates\Pages;

use App\Filament\Tenant\Clusters\BlockChain\Resources\Certificates\CertificateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCertificates extends ManageRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
