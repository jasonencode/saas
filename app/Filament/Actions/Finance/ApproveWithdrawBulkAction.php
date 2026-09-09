<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\WithdrawOrder;
use App\Services\Finance\WithdrawService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ApproveWithdrawBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'approveWithdrawBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量审核通过');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');

        $this->visible(fn (): bool => userCan(self::getDefaultName(), WithdrawOrder::class));

        $this->requiresConfirmation();
        $this->modalHeading('批量审核通过');
        $this->modalDescription('确定要通过选中的提现申请吗？');

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records): void {
            $orders = $records
                ->filter(fn (WithdrawOrder $order): bool => $order->status === WithdrawOrderStatus::Pending)
                ->values();

            if ($orders->isEmpty()) {
                $this->failureNotificationTitle('所选提现中没有待审核的申请');
                $this->failure();

                return;
            }

            try {
                $reviewerId = Filament::auth()->id();

                foreach ($orders as $order) {
                    service(WithdrawService::class)
                        ->review($order, true, $reviewerId);
                }

                $this->successNotificationTitle(sprintf('已审核通过 %d 条提现申请', $orders->count()));
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('批量审核失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
