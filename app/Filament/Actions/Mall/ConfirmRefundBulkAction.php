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

            $succeeded = 0;
            $failed = [];

            foreach ($refunds as $refund) {
                try {
                    service(RefundService::class)
                        ->processRefund($refund, Filament::auth()->user());
                    $succeeded++;
                } catch (Throwable $e) {
                    // 逐条收集：单条失败不影响已成功的记录，汇总后统一反馈
                    $failed[] = sprintf('%s：%s', $refund->no, $e->getMessage());
                }
            }

            if ($failed !== []) {
                $this->failureNotificationTitle(sprintf('已发起退款 %d 条，失败 %d 条', $succeeded, count($failed)));
                $this->failureNotificationBody(implode("\n", $failed));
                $this->failure();

                return;
            }

            $this->successNotificationTitle(sprintf('已发起退款 %d 条退款单', $succeeded));
            $this->success();
        });
    }
}
