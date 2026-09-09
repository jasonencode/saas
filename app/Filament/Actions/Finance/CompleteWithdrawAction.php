<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\WithdrawOrder;
use App\Services\Finance\WithdrawService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Throwable;

class CompleteWithdrawAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'completeWithdraw';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('确认打款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(fn (WithdrawOrder $record): bool => userCan(self::getDefaultName(), $record) && $record->status === WithdrawOrderStatus::Approved);

        $this->requiresConfirmation();
        $this->modalHeading('确认打款');
        $this->modalDescription('确认已完成线下打款？操作后将扣减冻结金额。');

        $this->schema([
            TextInput::make('payment_no')
                ->label('打款流水号')
                ->required()
                ->maxLength(64),
        ]);

        $this->action(function (WithdrawOrder $record, array $data): void {
            try {
                service(WithdrawService::class)
                    ->complete($record, $data['payment_no']);

                $this->successNotificationTitle('打款确认成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('打款确认失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
