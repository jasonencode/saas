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

        $this->label('批量确认退款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(function (HasTable $livewire): bool {
            return $livewire->activeTab === 'processing'
                && userCan(self::getDefaultName(), $livewire->getTable()->getModel());
        });

        $this->requiresConfirmation();
        $this->modalHeading('批量确认退款');
        $this->modalDescription('确定要对选中的退款单执行退款吗？退款金额将原路退回给用户。');

        $this->schema([
            Textarea::make('remark')
                ->label('退款备注')
                ->rows(3)
                ->maxLength(500),
            $this->getCurrentPasswordField(),
        ]);

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records, array $data): void {
            $refunds = $records
                ->filter(fn (Refund $refund): bool => $refund->status === RefundStatus::Processing)
                ->values();

            if ($refunds->isEmpty()) {
                $this->failureNotificationTitle('所选退款中没有可确认退款的申请');
                $this->failure();

                return;
            }

            try {
                foreach ($refunds as $refund) {
                    service(RefundService::class)
                        ->confirmRefund($refund, Filament::auth()->user(), $data['remark'] ?? null);
                }

                $this->successNotificationTitle(sprintf('已确认退款 %d 条退款单', $refunds->count()));
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
