<?php

namespace App\Filament\Actions\Campaign;

use App\Models\Redpack;
use App\Services\RedpackService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownloadCodeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'downloadCode';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下载红包码');
        $this->icon(Heroicon::OutlinedInboxArrowDown);
        $this->hidden(fn (Redpack $redpack) => $redpack->codes()->count() === 0);
        $this->action(function (Redpack $record, RedpackService $service) {
            return $service->exportCodesToZip($record);
        });
    }
}
