<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\System\Administrator;
use App\Models\System\AdminRole;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:administrators')]
class AdministratorSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '管理员填充';
    }

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $adminCount = (int) text(
            label: '创建管理员数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $roleCount = (int) text(
            label: '创建角色数量',
            default: '2',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充管理员数据...', $tenant->name));

        // 创建角色
        $progressBar = $this->output->createProgressBar($roleCount);
        $progressBar->setMessage('创建角色');
        $progressBar->start();

        $roles = collect();
        for ($i = 0; $i < $roleCount; $i++) {
            $roles->push(AdminRole::factory()->create([
                'tenant_id' => $tenantId,
            ]));
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // 创建管理员并分配角色
        $progressBar = $this->output->createProgressBar($adminCount);
        $progressBar->setMessage('创建管理员');
        $progressBar->start();

        for ($i = 0; $i < $adminCount; $i++) {
            $admin = Administrator::factory()->tenantAdmin()->create();

            // 分配 1-2 个角色
            $roleCount = random_int(1, min(2, $roles->count()));
            $admin->roles()->attach($roles->random($roleCount));

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("管理员数据填充完成！共创建 {$roleCount} 个角色，{$adminCount} 个管理员");
    }
}
