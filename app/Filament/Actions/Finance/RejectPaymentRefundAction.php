<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Models\Finance\PaymentRefund;
use App\Services\Finance\PaymentRefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Throwable;

class RejectPaymentRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'rejectRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核驳回');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');

        $this->visible(fn (PaymentRefund $record): bool => userCan(self::getDefaultName(), $record) && $record->status === PaymentRefundStatus::Pending);

        $this->modalHeading('审核驳回');

        $this->schema([
            Textarea::make('rejected_reason')
                ->label('驳回原因')
                ->required()
                ->rows(3)
                ->maxLength(255),
        ]);

        $this->action(function (PaymentRefund $record, array $data): void {
            try {
                service(PaymentRefundService::class)
                    ->reject($record, (int) Filament::auth()->id(), $data['rejected_reason']);

                $this->successNotificationTitle('已驳回');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('驳回失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
