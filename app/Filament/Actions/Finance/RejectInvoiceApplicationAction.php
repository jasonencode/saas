<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Models\Finance\InvoiceApplication;
use App\Services\Finance\InvoiceService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Throwable;

class RejectInvoiceApplicationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rejectInvoiceApplication';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('拒绝开票');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');

        $this->visible(fn (InvoiceApplication $record): bool => userCan(self::getDefaultName(), $record) && $record->status === InvoiceApplicationStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('拒绝开票申请');
        $this->modalDescription('确定要拒绝此开票申请吗？');

        $this->schema([
            Textarea::make('remark')
                ->label('拒绝原因')
                ->required()
                ->rows(3)
                ->maxLength(500),
        ]);

        $this->action(function (InvoiceApplication $record, InvoiceService $service, array $data): void {
            try {
                $service->reject($record, $data['remark']);

                $this->successNotificationTitle('已拒绝开票申请');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
