<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ApproveRefundBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'approveRefundBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量审核通过');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');

        $this->visible(function (HasTable $livewire): bool {
            return $livewire->activeTab === 'pending'
                && userCan(self::getDefaultName(), $livewire->getTable()->getModel());
        });

        $this->requiresConfirmation();
        $this->modalHeading('批量审核通过');
        $this->modalDescription('确定要通过选中的退款申请吗？');

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records): void {
            $refunds = $records
                ->filter(fn (Refund $refund): bool => $refund->status === RefundStatus::Pending)
                ->values();

            if ($refunds->isEmpty()) {
                $this->failureNotificationTitle('所选退款中没有待审核的申请');
                $this->failure();

                return;
            }

            try {
                foreach ($refunds as $refund) {
                    service(RefundService::class)
                        ->approveRefund($refund, Filament::auth()->user());
                }

                $this->successNotificationTitle(sprintf('已审核通过 %d 条退款申请', $refunds->count()));
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
