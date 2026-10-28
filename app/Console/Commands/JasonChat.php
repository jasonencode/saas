<?php

namespace App\Console\Commands;

use App\Ai\Agents\OrderSearch;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\TextDelta;

use function Laravel\Prompts\text;

#[Signature('jason:chat')]
class JasonChat extends Command
{
    protected $description = 'Interactive AI chat in terminal';

    public function handle(): void
    {
        $history = [];

        $this->info('交互式 AI 对话（输入 q 退出）'.PHP_EOL);

        while (true) {
            $input = text('你');

            if (in_array(mb_strtolower(trim($input)), ['q', 'quit', 'exit'])) {
                $this->info('再见！');

                return;
            }

            if (trim($input) === '') {
                continue;
            }

            echo PHP_EOL;

            $stream = (new OrderSearch($history))->stream($input);

            $text = '';

            foreach ($stream as $event) {
                if ($event instanceof TextDelta) {
                    echo $event->delta;
                    $text .= $event->delta;
                } elseif ($event instanceof ReasoningDelta) {
                    // 深度思考内容，灰色输出；不需要可以删掉这个分支
                    echo "\033[90m{$event->delta}\033[0m";
                } elseif ($event instanceof Error) {
                    echo PHP_EOL.'[stream error] '.$event->message.PHP_EOL;
                }
            }

            echo PHP_EOL.PHP_EOL;

            // 累积会话历史，下一轮带上
            $history[] = new Message('user', $input);
            $history[] = new Message('assistant', $text);
        }
    }
}
