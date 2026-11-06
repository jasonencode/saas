<?php

namespace App\Filament\Actions\Campaign;

use App\Enums\Campaign\SeckillSessionStatus;
use App\Models\Campaign\SeckillSession;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Throwable;

class SeckillSessionPreheatAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'seckillSessionPreheat';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('预热');
        $this->icon(Heroicon::OutlinedFire);
        $this->requiresConfirmation();
        $this->modalHeading('预热场次到 Redis');
        $this->modalDescription('将当前场次的名额与状态写入 Redis，开抢时直接从 Redis 原子扣减，避免高并发下数据库压力。已预热过的场次可重复执行，会以最新数据库名额覆盖。');
        $this->modalSubmitActionLabel('确认预热');

        $this->visible(fn (SeckillSession $record): bool => userCan('toggleStatus', $record)
            && in_array($record->status, [
                SeckillSessionStatus::Scheduled,
                SeckillSessionStatus::Running,
            ], true));

        $this->action(function (SeckillSession $record): void {
            try {
                service(RedisSeckillStockCounter::class)->prepare($record);

                $this->successNotificationTitle('场次已预热到 Redis');
                $this->success();
            } catch (Throwable $exception) {
                report($exception);

                $this->failureNotificationTitle('预热失败');
                $this->failureNotificationBody($exception->getMessage());
                $this->failure();
            }
        });
    }
}
