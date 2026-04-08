<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\Actions;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\InvoiceType;
use App\Events\Finance\InvoiceIssued;
use App\Models\Finance\Invoice;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class IssueInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'issueInvoice';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label('开具发票');

        $this->icon('heroicon-o-document-text');

        $this->schema([
            TextInput::make('invoice_no')
                ->label('发票号码')
                ->required(),
            DatePicker::make('invoice_date')
                ->label('开票日期')
                ->required(),
            Select::make('type')
                ->label('发票类型')
                ->options(InvoiceType::class)
                ->required(),
            TextInput::make('recipient_email')
                ->label('接收邮箱')
                ->email(),
            TextInput::make('recipient_phone')
                ->label('接收电话'),
            TextInput::make('creator')
                ->label('开票人')
                ->required(),
        ]);
        $this->action(function (array $data, $record) {
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

            Notification::make()
                ->success()
                ->title('发票开具成功')
                ->body('发票已成功开具并发送给用户')
                ->send();
        })
            ->visible(fn ($record) => $record->status === InvoiceApplicationStatus::Pending);
    }
}
