<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Finance\ApproveWithdrawAction;
use App\Filament\Actions\Finance\CompleteWithdrawAction;
use App\Filament\Actions\Finance\RejectWithdrawAction;
use App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders\WithdrawOrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWithdrawOrder extends ViewRecord
{
    protected static string $resource = WithdrawOrderResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->no;
    }

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ApproveWithdrawAction::make(),
            RejectWithdrawAction::make(),
            CompleteWithdrawAction::make(),
        ];
    }
}
