<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Modules\Pages;

use App\Filament\Actions\Setting\InstallModuleAction;
use App\Filament\Backend\Clusters\Setting\Resources\Modules\ModuleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageModules extends ManageRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getListeners(): array
    {
        return [
            'refreshTable' => '$refresh',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            InstallModuleAction::make(),
            Action::make('refresh')
                ->label('刷新')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function(Action $action) {
                }),
        ];
    }
}
