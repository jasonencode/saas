<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\text;

#[Signature('seed:realnames')]
#[CommandLabel('用户实名填充')]
class UserRealnameSeeder extends BaseCommand
{
    public function handle(): void
    {
        $count = (int) text(
            label: '创建实名认证数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $users = User::whereDoesntHave('realname')
            ->limit($count)
            ->get();

        if ($users->isEmpty()) {
            $this->error('没有未实名的用户');

            return;
        }

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $type = fake()->randomElement(RealnameType::cases());
            $status = fake()->randomElement(RealnameStatus::cases());

            $factory = UserRealname::factory()
                ->state(['user_id' => $user->getKey()]);

            if ($type === RealnameType::Enterprise) {
                $factory->enterprise();
            }

            if ($status === RealnameStatus::Approved) {
                $factory->approved();
            } elseif ($status === RealnameStatus::Rejected) {
                $factory->rejected();
            }

            $factory->create();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("实名认证数据填充完成！共创建 {$users->count()} 条");
    }
}
