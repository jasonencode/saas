<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Users\Pages;

use App\Export\UserExport;
use App\Filament\Actions\Common\CustomExportAction;
use App\Filament\Backend\Clusters\Users\Resources\Users\UserResource;
use App\Filament\Exports\UserExporter;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ActionGroup::make([
                Actions\ExportAction::make()
                    ->exporter(UserExporter::class),
                CustomExportAction::make()
                ->exporter(UserExport::class)
            ])
                ->color('info')
                ->label('导出')
                ->button(),
        ];
    }
}
