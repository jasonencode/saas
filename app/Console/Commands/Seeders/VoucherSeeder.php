<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\Finance\Plan;
use App\Models\Finance\Voucher;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:vouchers')]
class VoucherSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '结算凭据填充';
    }

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $planCount = (int) text(
            label: '创建计划数量',
            default: '2',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $voucherCount = (int) text(
            label: '创建凭据数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充凭据数据...', $tenant->name));

        // 创建计划
        $progressBar = $this->output->createProgressBar($planCount);
        $progressBar->setMessage('创建计划');
        $progressBar->start();

        $plans = collect();
        for ($i = 0; $i < $planCount; $i++) {
            $plans->push(Plan::factory()->create([
                'tenant_id' => $tenantId,
            ]));
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // 创建凭据
        $users = User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->limit($voucherCount)
            ->get();

        if ($users->isEmpty()) {
            $this->error('该租户下没有用户，请先创建用户');

            return;
        }

        $progressBar = $this->output->createProgressBar($voucherCount);
        $progressBar->setMessage('创建凭据');
        $progressBar->start();

        for ($i = 0; $i < $voucherCount; $i++) {
            Voucher::factory()->create([
                'tenant_id' => $tenantId,
                'user_id' => $users->random()->getKey(),
                'plan_id' => $plans->random()->getKey(),
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("凭据数据填充完成！共创建 {$planCount} 个计划，{$voucherCount} 个凭据");
    }
}
