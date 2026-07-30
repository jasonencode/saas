<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\Pages;

use App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\LotteryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLotteries extends ManageRecords
{
    protected static string $resource = LotteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
