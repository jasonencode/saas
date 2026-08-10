<?php

namespace App\Console\Commands\Seeders;

use App\Services\User\TenantService;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\text;

use Overtrue\Pinyin\Pinyin;

#[Signature('seed:tenants')]
class TenantSeeder extends Command
{
    public function handle(): void
    {
        $count = (int) text(
            label: '创建租户数量',
            default: '2',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $service = service(TenantService::class);

        for ($i = 0; $i < $count; $i++) {
            $name = fake('zh_CN')->company();
            $service->create([
                'name' => $name,
                'slug' => Pinyin::abbr($name)->join(''),
                'expired_at' => Carbon::now()->addYear(),
                'status' => true,
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('租户数据填充完成！');
    }
}
