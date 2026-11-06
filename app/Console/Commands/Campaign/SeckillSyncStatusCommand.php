<?php

namespace App\Console\Commands\Campaign;

use App\Enums\Campaign\SeckillSessionStatus;
use App\Models\Campaign\SeckillSession;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('seckill:sync-status')]
class SeckillSyncStatusCommand extends Command
{
    protected $description = '同步秒杀场次状态并预热即将开始的场次';

    public function handle(): void
    {
        $now = now();

        // 到点开抢
        SeckillSession::where('status', SeckillSessionStatus::Scheduled->value)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->update(['status' => SeckillSessionStatus::Running->value]);

        // 到点结束
        SeckillSession::whereIn('status', [
            SeckillSessionStatus::Scheduled->value,
            SeckillSessionStatus::Running->value,
        ])
            ->where('end_at', '<=', $now)
            ->update(['status' => SeckillSessionStatus::Ended->value]);

        // 售罄结束
        SeckillSession::where('status', SeckillSessionStatus::Running->value)
            ->whereColumn('sold', '>=', 'stock')
            ->update(['status' => SeckillSessionStatus::Ended->value]);

        // 预热：即将开始与刚开始的场次
        $sessions = SeckillSession::whereIn('status', [
            SeckillSessionStatus::Scheduled->value,
            SeckillSessionStatus::Running->value,
        ])
            ->where('end_at', '>', $now)
            ->where('start_at', '<=', $now->copy()->addMinutes((int) config('seckill.preheat_minutes', 5)))
            ->get();

        $counter = service(RedisSeckillStockCounter::class);

        foreach ($sessions as $session) {
            $counter->prepare($session);
        }

        $this->info(sprintf('秒杀场次同步完成，预热 %d 场', $sessions->count()));
    }
}
