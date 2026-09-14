<?php

namespace App\Console\Commands\Seeders;

use App\Models\Finance\RechargeOrder;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:recharge-orders')]
class RechargeOrderSeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $count = (int) text(
            label: '创建充值订单数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充充值订单数据...', $tenant->name));

        $users = User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->limit($count)
            ->get();

        if ($users->isEmpty()) {
            $this->error('该租户下没有用户，请先创建用户');

            return;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $status = fake()->randomElement(['pending', 'paid', 'completed']);

            $factory = RechargeOrder::factory()
                ->state([
                    'tenant_id' => $tenantId,
                    'user_id' => $users->random()->getKey(),
                ]);

            if ($status === 'paid') {
                $factory->paid();
            } elseif ($status === 'completed') {
                $factory->completed();
            }

            $factory->create();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("充值订单数据填充完成！共创建 {$count} 笔订单");
    }
}
