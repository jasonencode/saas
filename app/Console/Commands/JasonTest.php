<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jason:test')]
class JasonTest extends Command
{
    protected $description = 'Test command for Jason';

    public function handle(): void
    {
        //
    }
}
