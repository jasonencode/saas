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

class ConfirmReceiveAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirmReceive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('确认签收');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('确认签收退货');
        $this->modalDescription('确认已收到退货商品？确认后将自动进入退款处理。');
        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::Shipping);
        $this->form([
            Textarea::make('remark')
                ->label('签收备注')
                ->rows(3)
                ->maxLength(500),
        ]);
        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->confirmReceive($refund, Filament::auth()->user(), $data['remark'] ?? null);

                $this->successNotificationTitle('已签收，退款处理中');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
