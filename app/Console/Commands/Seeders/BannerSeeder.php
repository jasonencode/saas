<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\Mall\Banner;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:banners')]
class BannerSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '轮播图填充';
    }

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $count = (int) text(
            label: '创建轮播图数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充轮播图数据...', $tenant->name));

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            Banner::factory()->create([
                'tenant_id' => $tenantId,
                'sort' => $i,
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("轮播图数据填充完成！共创建 {$count} 条");
    }
}
