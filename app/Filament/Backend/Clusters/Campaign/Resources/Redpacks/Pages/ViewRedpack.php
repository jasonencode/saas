<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Pages;

use App\Filament\Actions\Campaign\DownloadCodeAction;
use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\RedpackResource;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Widgets\RedpackStatsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewRedpack extends ViewRecord
{
    protected static string $resource = RedpackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RefreshAction::make(),
            DownloadCodeAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name.' - 红包详情';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RedpackStatsWidget::make(),
        ];
    }
}
