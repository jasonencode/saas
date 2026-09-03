<?php

namespace App\Console\Commands\User;

use App\Models\Finance\UserAccount;
use App\Models\User\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:user:init-accounts')]
#[Description('为没有账户记录的用户初始化 UserAccount')]
class InitUserAccountsCommand extends Command
{
    public function handle(): int
    {
        $this->info('开始初始化用户账户...');

        $total = 0;

        User::whereDoesntHave('account')
            ->chunkById(100, function ($users) use (&$total) {
                foreach ($users as $user) {
                    UserAccount::create([
                        'user_id' => $user->getKey(),
                        'balance' => 0,
                        'frozen_balance' => 0,
                        'points' => 0,
                        'frozen_points' => 0,
                    ]);

                    $total++;
                    $this->line("已为用户 [{$user->getKey()}] 创建账户");
                }
            });

        $this->info("任务执行完毕，共初始化 $total 个用户账户。");

        return self::SUCCESS;
    }
}
