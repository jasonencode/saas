<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Voucher;
use App\Services\Finance\SettlementService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

/**
 * 手工执行结算
 *
 * 结算链路不再依赖自动触发，待执行或失败的凭据需要人工确认后重跑。
 */
class ExecuteVoucherAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'executeVoucher';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('执行结算');
        $this->icon(Heroicon::OutlinedPlayCircle);

        $this->visible(fn (Voucher $record): bool => userCan(self::getDefaultName(), $record)
            && in_array($record->status, [VoucherStatus::Pending, VoucherStatus::Failure], true));

        $this->requiresConfirmation();
        $this->modalHeading('执行结算');
        $this->modalDescription('将按计划顺序执行全部启用任务，成功前不会落库；失败后可在确认问题后重试。');

        $this->action(function (Voucher $record): void {
            try {
                service(SettlementService::class)->execute($record);

                $this->successNotificationTitle('结算执行成功');
                $this->success();
            } catch (Throwable $e) {
                report($e);

                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
