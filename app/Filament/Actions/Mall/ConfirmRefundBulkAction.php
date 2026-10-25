<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Filament\Actions\Concerns\ConfirmsCurrentPassword;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ConfirmRefundBulkAction extends BulkAction
{
    use ConfirmsCurrentPassword;

    public static function getDefaultName(): ?string
    {
        return 'confirmRefundBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量发起退款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(function (HasTable $livewire): bool {
            return $livewire->activeTab === 'processing'
                && userCan(self::getDefaultName(), $livewire->getTable()->getModel());
        });

        $this->requiresConfirmation();
        $this->modalHeading('批量发起退款');
        $this->modalDescription('确定要对选中的退款单发起退款吗？将创建支付退款单，财务审核后按原支付通道退回。');

        $this->schema([
            $this->getCurrentPasswordField(),
        ]);

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records): void {
            $refunds = $records
                ->filter(fn (Refund $refund): bool => $refund->status === RefundStatus::Processing)
                ->values();

            if ($refunds->isEmpty()) {
                $this->failureNotificationTitle('所选退款中没有可发起退款的申请');
                $this->failure();

                return;
            }

            try {
                foreach ($refunds as $refund) {
                    service(RefundService::class)
                        ->processRefund($refund, Filament::auth()->user());
                }

                $this->successNotificationTitle(sprintf('已发起退款 %d 条退款单', $refunds->count()));
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('批量发起退款失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
