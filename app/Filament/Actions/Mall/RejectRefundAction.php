<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Throwable;

class RejectRefundAction extends Action
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

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('审核驳回');
        $this->modalDescription('确定要驳回此退款申请吗？');

        $this->schema([
            Textarea::make('remark')
                ->label('驳回原因')
                ->required()
                ->rows(3)
                ->maxLength(500),
        ]);

        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->rejectRefund($refund, Filament::auth()->user(), $data['remark']);

                $this->successNotificationTitle('已驳回');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
