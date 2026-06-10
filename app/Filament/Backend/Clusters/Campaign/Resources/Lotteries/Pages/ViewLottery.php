<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\LotteryResource;
use App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\Widgets\LotteryStatsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewLottery extends ViewRecord
{
    protected static string $resource = LotteryResource::class;

    public function getTitle(): string
    {
        return $this->record->name.' - 抽奖详情';
    }

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RefreshAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LotteryStatsWidget::make(),
        ];
    }
}
