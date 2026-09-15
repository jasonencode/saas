<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Models\Finance\WithdrawOrder;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:withdraw-orders')]
#[CommandLabel('提现订单填充')]
class WithdrawOrderSeeder extends BaseCommand
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $count = (int) text(
            label: '创建提现订单数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充提现订单数据...', $tenant->name));

        $users = User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->limit($count)
            ->get();

        if ($users->isEmpty()) {
            $this->error('该租户下没有用户，请先创建用户');

            return;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $status = fake()->randomElement(['pending', 'approved', 'completed', 'rejected']);

            $factory = WithdrawOrder::factory()
                ->state([
                    'user_id' => $users->random()->getKey(),
                ]);

            if ($status === 'approved') {
                $factory->approved();
            } elseif ($status === 'completed') {
                $factory->paid();
            } elseif ($status === 'rejected') {
                $factory->rejected();
            }

            $factory->create();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("提现订单数据填充完成！共创建 {$count} 笔订单");
    }
}
