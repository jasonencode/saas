<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\CertificateResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
