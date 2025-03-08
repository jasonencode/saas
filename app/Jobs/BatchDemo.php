<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class BatchDemo extends BaseJob
{
    use Batchable;

    public int $tries = 5;

    public function __construct()
    {
        //
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120, 240];
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle(): void
    {
        sleep(5);
    }
}
