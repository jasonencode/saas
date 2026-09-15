<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\User\Address;
use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\text;

#[Signature('seed:addresses')]
class AddressSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '地址填充';
    }

    public function handle(): void
    {
        $count = (int) text(
            label: '为随机用户创建地址数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $users = User::withCount('addresses')
            ->having('addresses_count', '<', 5)
            ->limit((int) ceil($count / 2))
            ->get();

        if ($users->isEmpty()) {
            $this->error('没有可创建地址的用户');

            return;
        }

        $this->info("开始为 {$users->count()} 个用户填充地址数据...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $created = 0;
        while ($created < $count && $users->isNotEmpty()) {
            $user = $users->random();

            Address::factory()->create([
                'user_id' => $user->getKey(),
                'is_default' => $user->addresses()->count() === 0,
            ]);

            $created++;
            $progressBar->advance();

            if ($user->addresses()->count() >= 5) {
                $users = $users->filter(fn ($u) => $u->getKey() !== $user->getKey());
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("地址数据填充完成！共创建 {$created} 条");
    }
}
