<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('process:demo')]
#[Description('进度条使用方法演示')]
class ProcessDemo extends Command
{
    public function handle(): void
    {
        $count = 20;
        $this->info('开始');
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();
//        Config::set('hashing.bcrypt.rounds', 15);

        $start = microtime(true);
        for ($i = 0; $i < $count; $i++) {
            $user = User::create([
                'username' => fake('zh_CN')->phoneNumber(),
                'password' => 123456,
            ]);

            $user->profile->nickname = fake('zh_CN')->name();
            $user->profile->save();
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();
        $this->info(sprintf('总耗时：%s毫秒', (microtime(true) - $start) * 1000));
    }
}
