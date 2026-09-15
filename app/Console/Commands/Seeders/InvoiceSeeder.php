<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\Finance\InvoiceTitle;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:invoices')]
class InvoiceSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '发票填充';
    }

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $titleCount = (int) text(
            label: '创建发票抬头数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充发票数据...', $tenant->name));

        // 获取或创建用户
        $users = User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->limit($titleCount)
            ->get();

        if ($users->isEmpty()) {
            $this->error('该租户下没有用户，请先创建用户');

            return;
        }

        // 创建发票抬头
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->setMessage('创建发票抬头');
        $progressBar->start();

        foreach ($users as $user) {
            InvoiceTitle::factory()->create([
                'user_id' => $user->getKey(),
                'tenant_id' => $tenantId,
            ]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("发票数据填充完成！共创建 {$users->count()} 个发票抬头");
    }
}
