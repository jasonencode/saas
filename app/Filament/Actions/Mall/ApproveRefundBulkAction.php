<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Filament\Actions\Concerns\ConfirmsCurrentPassword;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ApproveRefundBulkAction extends BulkAction
{
    use ConfirmsCurrentPassword;

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

        $this->schema([
            Textarea::make('remark')
                ->label('审核备注')
                ->rows(3)
                ->maxLength(500),
            $this->getCurrentPasswordField(),
        ]);

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records, array $data): void {
            $refunds = $records
                ->filter(fn (Refund $refund): bool => $refund->status === RefundStatus::Pending)
                ->values();

            if ($refunds->isEmpty()) {
                $this->failureNotificationTitle('所选退款中没有待审核的申请');
                $this->failure();

                return;
            }

            $succeeded = 0;
            $failed = [];

            foreach ($refunds as $refund) {
                try {
                    service(RefundService::class)
                        ->approveRefund($refund, Filament::auth()->user(), $data['remark'] ?? null);
                    $succeeded++;
                } catch (Throwable $e) {
                    // 逐条收集：单条失败不影响已成功的记录，汇总后统一反馈
                    $failed[] = sprintf('%s：%s', $refund->no, $e->getMessage());
                }
            }

            if ($failed !== []) {
                $this->failureNotificationTitle(sprintf('已审核通过 %d 条，失败 %d 条', $succeeded, count($failed)));
                $this->failureNotificationBody(implode("\n", $failed));
                $this->failure();

                return;
            }

            $this->successNotificationTitle(sprintf('已审核通过 %d 条退款申请', $succeeded));
            $this->success();
        });
    }
}
