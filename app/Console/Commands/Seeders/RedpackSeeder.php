<?php

namespace App\Console\Commands\Seeders;

use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:redpacks')]
class RedpackSeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::pluck('name', 'id')->toArray(),
        );

        $redpackCount = (int) text(
            label: '创建红包活动数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $codeCount = (int) text(
            label: '每个活动生成红包码数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $progressBar = $this->output->createProgressBar($redpackCount);
        $progressBar->start();

        for ($i = 0; $i < $redpackCount; $i++) {
            $redpack = Redpack::factory()->create([
                'tenant_id' => $tenantId,
            ]);

            RedpackCode::factory()
                ->count($codeCount)
                ->create([
                    'redpack_id' => $redpack->getKey(),
                ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("红包数据填充完成！共创建 {$redpackCount} 个活动，{$codeCount} 个红包码/活动");
    }
}
