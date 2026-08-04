<?php

namespace App\Console\Commands\Seeders;

use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

#[Signature('seed:users')]
class UserSeeder extends Command
{
    public function handle(): void
    {
        $count = (int) text(
            label: '创建用户数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $user = User::create([
                'username' => fake('zh_CN')->phoneNumber(),
                'password' => bcrypt('123456'),
            ]);

            $user->profile->nickname = fake('zh_CN')->name();
            $user->profile->save();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('用户数据填充完成！');
    }
}
