<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\Mall\Express;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\text;

#[Signature('seed:expresses')]
class ExpressSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '快递填充';
    }

    public function handle(): void
    {
        $count = (int) text(
            label: '创建快递公司数量',
            default: '6',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $this->info('开始填充快递公司数据...');

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            Express::factory()->create([
                'sort' => $i,
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("快递公司数据填充完成！共创建 {$count} 家");
    }
}
