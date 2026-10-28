<?php

namespace App\Console\Commands;

use App\Ai\Agents\OrderSearch;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\TextDelta;

#[Signature('jason:test')]
class JasonTest extends Command
{
    protected $description = 'Test command for Jason';

    public function handle(): void
    {
        $stream = (new OrderSearch)
            ->stream('halo');

        foreach ($stream as $event) {
            if ($event instanceof TextDelta) {
                echo $event->delta;
            } elseif ($event instanceof ReasoningDelta) {
                // 深度思考内容，灰色输出；不需要可以删掉这个分支
                echo "\033[90m{$event->delta}\033[0m";
            } elseif ($event instanceof Error) {
                echo PHP_EOL.'[stream error] '.$event->message.PHP_EOL;
            }
        }

        echo PHP_EOL;
    }
}
