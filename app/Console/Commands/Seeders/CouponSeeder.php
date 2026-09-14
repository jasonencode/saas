<?php

namespace App\Console\Commands\Seeders;

use App\Models\Campaign\Coupon;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:coupons')]
class CouponSeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $count = (int) text(
            label: '创建优惠券数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充优惠券数据...', $tenant->name));

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $type = fake()->randomElement(['fixed', 'percent']);

            $factory = Coupon::factory()
                ->state(['tenant_id' => $tenantId]);

            if ($type === 'fixed') {
                $factory->fixed();
            } else {
                $factory->percent();
            }

            $factory->create();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("优惠券数据填充完成！共创建 {$count} 张优惠券");
    }
}
