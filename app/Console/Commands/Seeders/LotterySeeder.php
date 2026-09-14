<?php

namespace App\Console\Commands\Seeders;

use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:lotteries')]
class LotterySeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $lotteryCount = (int) text(
            label: '创建抽奖活动数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $prizeCount = (int) text(
            label: '每个活动奖品数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充抽奖数据...', $tenant->name));

        $progressBar = $this->output->createProgressBar($lotteryCount);
        $progressBar->start();

        for ($i = 0; $i < $lotteryCount; $i++) {
            $lottery = Lottery::factory()->create([
                'tenant_id' => $tenantId,
            ]);

            // 创建谢谢参与奖品
            LotteryPrize::factory()->none()->create([
                'lottery_id' => $lottery->getKey(),
            ]);

            // 创建其他奖品
            LotteryPrize::factory()
                ->count($prizeCount - 1)
                ->create([
                    'lottery_id' => $lottery->getKey(),
                ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("抽奖数据填充完成！共创建 {$lotteryCount} 个活动，{$prizeCount} 个奖品/活动");
    }
}
