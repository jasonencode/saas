<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\WithdrawOrder;
use App\Services\Finance\WithdrawService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Throwable;

class RejectWithdrawAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rejectWithdraw';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核拒绝');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');

        $this->visible(fn (WithdrawOrder $record): bool => userCan(self::getDefaultName(), $record) && $record->status === WithdrawOrderStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('审核拒绝');
        $this->modalDescription('确定要拒绝此提现申请吗？拒绝后金额将退还到用户余额。');

        $this->schema([
            Textarea::make('reject_reason')
                ->label('拒绝原因')
                ->required()
                ->rows(3)
                ->maxLength(255),
        ]);

        $this->action(function (WithdrawOrder $record, array $data): void {
            try {
                service(WithdrawService::class)
                    ->review($record, false, Filament::auth()->id(), $data['reject_reason']);

                $this->successNotificationTitle('已拒绝提现申请');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('拒绝失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
