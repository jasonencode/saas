<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Coupons\Pages;

use Filament\Resources\Pages\ManageRecords;
use App\Filament\Backend\Clusters\Mall\Resources\Coupons\CouponResource;
use Filament\Actions;

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
