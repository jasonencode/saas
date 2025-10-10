<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Test extends Command
{
    protected $signature = 'app:test';

    protected $description = '命令行的测试入口';

    public function handle(): void
    {
    }
}
