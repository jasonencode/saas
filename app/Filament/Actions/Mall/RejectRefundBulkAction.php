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

class RejectRefundBulkAction extends BulkAction
{
    use ConfirmsCurrentPassword;

    public static function getDefaultName(): ?string
    {
        return 'rejectRefundBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量驳回');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');

        $this->visible(function (HasTable $livewire): bool {
            return $livewire->activeTab === 'pending'
                && userCan(self::getDefaultName(), $livewire->getTable()->getModel());
        });

        $this->requiresConfirmation();
        $this->modalHeading('批量驳回');
        $this->modalDescription('确定要驳回选中的退款申请吗？');

        $this->schema([
            Textarea::make('remark')
                ->label('驳回原因')
                ->required()
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

            try {
                foreach ($refunds as $refund) {
                    service(RefundService::class)
                        ->rejectRefund($refund, Filament::auth()->user(), $data['remark']);
                }

                $this->successNotificationTitle(sprintf('已驳回 %d 条退款申请', $refunds->count()));
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
