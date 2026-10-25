<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Services\Finance\PaymentRefundService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

class CompletePaymentRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'completeRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('确认到账');
        $this->icon(Heroicon::OutlinedCheckBadge);
        $this->color('success');

        $this->visible(fn (PaymentRefund $record): bool => userCan(self::getDefaultName(), $record) && $record->status === PaymentRefundStatus::Processing);

        $this->requiresConfirmation();
        $this->modalHeading('确认退款到账');
        $this->modalDescription('仅用于微信异步退款已核对到账的场景，确认后售后单将同步完成。');

        $this->action(function (PaymentRefund $record): void {
            try {
                service(PaymentRefundService::class)->complete($record);

                $this->successNotificationTitle('退款已确认到账');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('确认失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
