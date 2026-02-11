<?php

namespace App\Jobs;

use App\Contracts\Authenticatable;
use App\Models\System;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 基础队列任务类
 *
 * @module 通用
 */
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        SerializesModels;

    public string $connection = 'redis';

    public string $queue = 'default';

    public DateTimeInterface|DateInterval|array|int|null $delay = 0;

    public int $timeout = 30;

    public int $tries = 1;

    abstract public function handle(): void;

    public function delay(DateTimeInterface|DateInterval|array|int|null $delay = null): self
    {
        $this->delay = $delay;

        return $this;
    }

    public function backoff(): array
    {
        return [5, 10, 30];
    }

    protected function user(): Authenticatable
    {
        return System::find(2);
    }
}
