<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Services\Finance\PaymentRefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ApprovePaymentRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approveRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核通过');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');

        $this->visible(fn (PaymentRefund $record): bool => userCan(self::getDefaultName(), $record) && $record->status === PaymentRefundStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('审核通过');
        $this->modalDescription('通过后即可执行原路退回，确认操作？');

        $this->action(function (PaymentRefund $record): void {
            try {
                service(PaymentRefundService::class)
                    ->approve($record, (int) Filament::auth()->id());

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
