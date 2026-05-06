<?php

namespace App\Filament\Actions\Campaign;

use App\Models\Campaign\Redpack;
use App\Services\Campaign\RedpackService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\Response;

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
        $this->visible(fn (Redpack $redpack): bool => userCan(self::getDefaultName(), $redpack) && $redpack->codes()->count() > 0);
        $this->action(function (Redpack $record, RedpackService $service): Response {
            return $service->exportCodesToZip($record);
        });
    }
}
