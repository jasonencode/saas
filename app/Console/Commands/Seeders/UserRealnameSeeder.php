<?php

namespace App\Console\Commands\Seeders;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

#[Signature('seed:realnames')]
class UserRealnameSeeder extends Command
{
    public function handle(): void
    {
        $count = (int) text(
            label: '创建实名认证数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $users = User::query()
            ->whereDoesntHave('realname')
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

            $data = [
                'user_id' => $user->getKey(),
                'type' => $type,
                'status' => $status,
                'name' => $type === RealnameType::Personal
                    ? fake('zh_CN')->name()
                    : fake('zh_CN')->company(),
                'id_card_number' => $type === RealnameType::Personal
                    ? fake()->numerify('110101199001011234')
                    : null,
                'contact_person' => $type === RealnameType::Enterprise
                    ? fake('zh_CN')->name()
                    : null,
                'contact_phone' => fake('zh_CN')->phoneNumber(),
            ];

            if ($status === RealnameStatus::Approved) {
                $data['verified_at'] = fake()->dateTimeBetween('-1 year', 'now');
            } elseif ($status === RealnameStatus::Rejected) {
                $data['reject_reason'] = fake()->sentence();
            }

            UserRealname::create($data);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("实名认证数据填充完成！共创建 {$users->count()} 条");
    }
}
