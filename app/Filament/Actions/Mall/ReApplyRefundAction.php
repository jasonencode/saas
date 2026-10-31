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

class ReApplyRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reApplyRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重置申请');
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->color('warning');

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund)
            && in_array($refund->status, [RefundStatus::Rejected, RefundStatus::Cancelled, RefundStatus::Failed], true));

        $this->requiresConfirmation();
        $this->modalHeading('重置退款申请');
        $this->modalDescription('重置后退款单回到待审核，需要重新审核。');

        $this->schema([
            Textarea::make('remark')
                ->label('备注')
                ->rows(3)
                ->maxLength(500),
        ]);

        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->reApplyRefund($refund, Filament::auth()->user(), $data['remark'] ?? null);

                $this->successNotificationTitle('退款申请已重置');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('重置退款申请失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
