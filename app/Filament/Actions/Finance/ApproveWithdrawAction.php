<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\WithdrawOrder;
use App\Services\Finance\WithdrawService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ApproveWithdrawAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approveWithdraw';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核通过');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');

        $this->visible(fn (WithdrawOrder $record): bool => userCan(self::getDefaultName(), $record) && $record->status === WithdrawOrderStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('审核通过');
        $this->modalDescription('确定要通过此提现申请吗？通过后将进入打款流程。');

        $this->action(function (WithdrawOrder $record): void {
            try {
                service(WithdrawService::class)
                    ->review($record, true, Filament::auth()->id());

                $this->successNotificationTitle('审核通过');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('审核失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
