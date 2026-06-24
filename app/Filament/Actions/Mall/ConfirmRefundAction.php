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

class ConfirmRefundAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('确认退款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('确认退款');
        $this->modalDescription('确定要执行退款吗？退款金额将原路退回给用户。');
        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::Processing);
        $this->form([
            Textarea::make('remark')
                ->label('退款备注')
                ->rows(3)
                ->maxLength(500),
        ]);
        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->confirmRefund($refund, Filament::auth()->user(), $data['remark'] ?? null);

                $this->successNotificationTitle('退款完成');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'confirmRefund';
    }
}
