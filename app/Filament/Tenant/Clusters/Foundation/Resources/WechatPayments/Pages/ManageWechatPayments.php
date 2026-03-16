<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\WechatPayments\Pages;

use App\Filament\Tenant\Clusters\Foundation\Resources\WechatPayments\WechatPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageWechatPayments extends ListRecords
{
    protected static string $resource = WechatPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
