<?php

namespace App\Jobs;

use App\Models\System;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        SerializesModels;

    public string $connection = 'redis';

    public string $queue = 'default';

    public DateTimeInterface|DateInterval|array|int|null $delay = 0;

    public bool $deleteWhenMissingModels = true;

    public int $timeout = 30;

    public int $tries = 1;

    abstract public function handle(): void;

    /**
     * 设置延迟队列
     *
     * @param  DateInterval|DateTimeInterface|int|array|null  $delay
     * @return $this
     */
    public function delay(DateInterval|DateTimeInterface|int|array|null $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    public function onQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function user(): System
    {
        return System::find(2);
    }
}
