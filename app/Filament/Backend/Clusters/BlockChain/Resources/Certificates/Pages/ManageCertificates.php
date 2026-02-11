<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\Pages;

use App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\CertificateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
