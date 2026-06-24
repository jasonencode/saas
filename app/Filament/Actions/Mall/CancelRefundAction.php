<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class CancelRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancelRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消退款');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading('取消退款');
        $this->modalDescription('确定要取消此退款申请吗？取消后将无法恢复。');
        $this->visible(fn(Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::Pending);
        $this->action(function (Refund $refund): void {
            try {
                service(RefundService::class)
                    ->cancelRefund($refund, Filament::auth()->user());

                $this->successNotificationTitle('退款已取消');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
