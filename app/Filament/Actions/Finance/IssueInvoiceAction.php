<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\InvoiceType;
use App\Events\Finance\InvoiceIssued;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceApplication;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;

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
            Forms\Components\TextInput::make('invoice_no')
                ->label('发票号码')
                ->required(),
            Forms\Components\DatePicker::make('invoice_date')
                ->label('开票日期')
                ->required(),
            Forms\Components\Select::make('type')
                ->label('发票类型')
                ->options(InvoiceType::class)
                ->required(),
            Forms\Components\TextInput::make('recipient_email')
                ->label('接收邮箱')
                ->email(),
            Forms\Components\TextInput::make('recipient_phone')
                ->label('接收电话'),
            Forms\Components\TextInput::make('creator')
                ->label('开票人')
                ->required(),
        ]);

        $this->action(function (InvoiceApplication $record, array $data): void {
            // 更新申请状态为已批准
            $record->update([
                'status' => InvoiceApplicationStatus::Approved,
            ]);

            // 创建发票记录
            $invoice = Invoice::create([
                'user_id' => $record->user_id,
                'invoice_application_id' => $record->id,
                'invoice_no' => $data['invoice_no'],
                'invoice_date' => $data['invoice_date'],
                'type' => $data['type'],
                'amount' => $record->amount,
                'status' => InvoiceStatus::Issued,
                'recipient_email' => $data['recipient_email'],
                'recipient_phone' => $data['recipient_phone'],
                'creator' => $data['creator'],
            ]);

            // 触发发票开具事件
            event(new InvoiceIssued($invoice));

            $this->successNotificationTitle('发票开具成功');
            $this->success();
        });
    }
}
