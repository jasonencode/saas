<?php

namespace App\Console\Commands\Campaign;

use App\Enums\Campaign\SeckillSessionStatus;
use App\Models\Campaign\SeckillSession;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('seckill:reconcile')]
class SeckillReconcileCommand extends Command
{
    protected $description = '对账秒杀场次 Redis 名额与数据库名额';

    public function handle(): void
    {
        $counter = service(RedisSeckillStockCounter::class);
        $mismatches = 0;

        SeckillSession::where('status', SeckillSessionStatus::Running->value)
            ->chunkById(100, function ($sessions) use ($counter, &$mismatches): void {
                foreach ($sessions as $session) {
                    $expected = $session->fresh()?->remaining() ?? 0;
                    $actual = $counter->remaining($session);

                    if ($actual === $expected) {
                        continue;
                    }

                    $mismatches++;

                    logger()->warning('seckill stock mismatch', [
                        'session_id' => $session->getKey(),
                        'expected' => $expected,
                        'actual' => $actual,
                    ]);
                }
            });

        $this->info(sprintf('秒杀名额对账完成，差异 %d 场', $mismatches));
    }
}
