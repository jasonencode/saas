<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\Pages;

use App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCoupons extends ManageRecords
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
