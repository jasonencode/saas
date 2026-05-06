<?php

namespace App\Filament\Actions\Setting;

use Filament\Actions\Action;
use Filament\Actions\Exports\Models\Export;
use Filament\Support\Icons\Heroicon;

class DownloadExportCsvAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'downloadExportCsv';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下载CSV');
        $this->icon(Heroicon::ArrowDownTray);
        $this->visible(fn (Export $record): bool => userCan(self::getDefaultName(), $record) && filled($record->completed_at));
        $this->url(function (Export $record): string {
            return route('filament.exports.download', ['export' => $record, 'format' => 'csv']);
        }, true);
    }
}
