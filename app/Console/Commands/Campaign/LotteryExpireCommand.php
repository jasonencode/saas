<?php

namespace App\Console\Commands\Campaign;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Models\Campaign\LotteryPrizeRecord;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('lottery:expire')]
#[Description('标记已结束活动的待兑奖记录为已过期')]
class LotteryExpireCommand extends Command
{
    public function handle(): int
    {
        $count = LotteryPrizeRecord::query()
            ->where('status', LotteryPrizeStatus::Pending->value)
            ->whereHas('lottery', function (Builder $builder): void {
                $builder->whereNotNull('end_at')
                    ->where('end_at', '<', now());
            })
            ->update(['status' => LotteryPrizeStatus::Expired->value]);

        $this->info(sprintf('已过期 %d 条抽奖奖品记录', $count));

        return self::SUCCESS;
    }
}
