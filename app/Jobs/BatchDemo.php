<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;

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


    public function handle(): void
    {
        sleep(5);
    }
}
