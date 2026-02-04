<?php

namespace App\Filament\Actions\Common;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\HtmlString;

/**
 * composer require maatwebsite/excel:^3.1
 */
class CustomExportAction extends Action
{
    protected string $exporter;

    public static function getDefaultName(): ?string
    {
        return 'dataExport';
    }

    public function exporter(string $exporter): static
    {
        $this->exporter = $exporter;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('导出');
        $this->color('info');
        $this->icon(Heroicon::ArrowDownTray);
        $this->requiresConfirmation();
        $this->modalSubmitActionLabel('导出');
        $this->modalContent(new HtmlString('同步导出数据，耗时较长，超过2000条的数据不建议使用同步导出功能。'));

        $this->action(function (HasTable $livewire) {
            return new $this->exporter($livewire->getTableQueryForExport());
        });
    }
}
