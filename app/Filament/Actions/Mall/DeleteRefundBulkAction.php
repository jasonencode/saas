<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class DeleteRefundBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'deleteRefundBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量删除');
        $this->icon(Heroicon::OutlinedTrash);
        $this->color('danger');

        $this->visible(fn (): bool => userCan(self::getDefaultName(), Refund::class));

        $this->requiresConfirmation();
        $this->modalHeading('批量删除退款单');
        $this->modalDescription('仅已拒绝、已取消的退款单会被删除。');

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records): void {
            $refunds = $records
                ->filter(fn (Refund $refund): bool => in_array($refund->status, [RefundStatus::Rejected, RefundStatus::Cancelled], true))
                ->values();

            if ($refunds->isEmpty()) {
                $this->failureNotificationTitle('所选退款单中没有可删除的记录');
                $this->failure();

                return;
            }

            try {
                foreach ($refunds as $refund) {
                    service(RefundService::class)
                        ->destroyRefund($refund, Filament::auth()->user());
                }

                $this->successNotificationTitle(sprintf('已删除 %d 条退款单', $refunds->count()));
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('批量删除失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
