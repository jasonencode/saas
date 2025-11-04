<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

abstract class BaseJob implements ShouldQueue
{
    use Queueable;

    public $connection = 'redis';

    public $queue = 'default';

    public $delay = 0;

    public int $timeout = 30;

    public int $tries = 1;

    abstract public function handle(): void;

    public function backoff(): array
    {
        return [5, 10, 30];
    }
}
