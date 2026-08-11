<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Finance\InvoiceType;
use App\Models\Finance\InvoiceApplication;
use App\Services\Finance\InvoiceService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Support\Icons\Heroicon;
use Throwable;

class IssueInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'issueInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('开具发票');
        $this->icon(Heroicon::OutlinedDocumentText);
        $this->visible(fn (InvoiceApplication $record): bool => userCan(self::getDefaultName(), $record) && $record->status === InvoiceApplicationStatus::Pending);

        $this->schema([
            Schemas\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('invoice_no')
                        ->label('发票号码')
                        ->required(),
                    Forms\Components\DatePicker::make('invoice_date')
                        ->label('开票日期')
                        ->required()
                        ->suffixIcon(Heroicon::OutlinedCalendar),
                    Forms\Components\Radio::make('type')
                        ->label('发票类型')
                        ->options(InvoiceType::class)
                        ->required(),
                    Forms\Components\TextInput::make('creator')
                        ->label('开票人')
                        ->required(),
                    Forms\Components\TextInput::make('recipient_email')
                        ->label('接收邮箱')
                        ->email(),
                    Forms\Components\TextInput::make('recipient_phone')
                        ->label('接收电话'),
                ]),
        ]);

        $this->action(function (InvoiceApplication $record, InvoiceService $service, array $data): void {
            try {
                $service->issue($record, $data);

                $this->successNotificationTitle('发票开具成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
