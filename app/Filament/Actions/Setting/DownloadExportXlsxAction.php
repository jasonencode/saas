<?php

namespace App\Filament\Actions\Setting;

use Filament\Actions\Action;
use Filament\Actions\Exports\Models\Export;
use Filament\Support\Icons\Heroicon;

class DownloadExportXlsxAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下载XLSX');
        $this->icon(Heroicon::ArrowDownTray);
        $this->visible(fn (Export $record): bool => userCan(self::getDefaultName(), $record) && filled($record->completed_at));
        $this->url(function (Export $record): string {
            return route('filament.exports.download', ['export' => $record, 'format' => 'xlsx']);
        }, true);
    }

    public static function getDefaultName(): ?string
    {
        return 'downloadExportXlsx';
    }
}
