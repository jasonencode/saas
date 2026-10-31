<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class DeleteRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'deleteRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('删除');
        $this->icon(Heroicon::OutlinedTrash);
        $this->color('danger');

        $this->visible(fn (Refund $refund): bool => userCan('delete', $refund));

        $this->requiresConfirmation();
        $this->modalHeading('删除退款单');
        $this->modalDescription('删除后退款单进入回收站，可在回收站恢复。');

        $this->action(function (Refund $refund): void {
            try {
                service(RefundService::class)
                    ->destroyRefund($refund, Filament::auth()->user());

                $this->successNotificationTitle('退款单已删除');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('删除退款单失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
