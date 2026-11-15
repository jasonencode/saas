<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Services\Finance\PaymentRefundService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

class CancelPaymentRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancelRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消退款');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->color('warning');

        $this->visible(fn (PaymentRefund $record): bool => userCan(self::getDefaultName(), $record) && $record->status === PaymentRefundStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('取消退款');
        $this->modalDescription('取消后需重新发起退款申请，确认取消？');

        $this->action(function (PaymentRefund $record): void {
            try {
                service(PaymentRefundService::class)->cancel($record);

                $this->successNotificationTitle('退款已取消');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('取消失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
