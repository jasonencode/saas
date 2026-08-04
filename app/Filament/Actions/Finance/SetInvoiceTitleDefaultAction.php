<?php

namespace App\Filament\Actions\Finance;

use App\Models\Finance\InvoiceTitle;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class SetInvoiceTitleDefaultAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setDefault';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('设为默认');
        $this->icon(Heroicon::OutlinedStar);
        $this->color('warning');

        $this->visible(fn (InvoiceTitle $record): bool => userCan(self::getDefaultName(), $record) && !$record->is_default);

        $this->requiresConfirmation();

        $this->action(function (InvoiceTitle $record): void {
            $record->setDefault();

            $this->successNotificationTitle('已设为默认发票抬头');
            $this->success();
        });
    }
}
