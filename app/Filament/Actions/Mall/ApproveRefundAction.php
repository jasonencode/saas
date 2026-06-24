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

class ApproveRefundAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核通过');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('审核通过');
        $this->modalDescription('确定要通过此退款申请吗？');
        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::Pending);
        $this->form([
            Textarea::make('remark')
                ->label('审核备注')
                ->rows(3)
                ->maxLength(500),
        ]);
        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->approveRefund($refund, Filament::auth()->user(), $data['remark'] ?? null);

                $this->successNotificationTitle('审核通过');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'approveRefund';
    }
}
