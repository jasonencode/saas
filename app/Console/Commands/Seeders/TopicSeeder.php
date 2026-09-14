<?php

namespace App\Console\Commands\Seeders;

use App\Models\Mall\Product;
use App\Models\Mall\Topic;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:topics')]
class TopicSeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $count = (int) text(
            label: '创建专题数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $productPerTopic = (int) text(
            label: '每个专题关联商品数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value >= 0 ? null : '请输入大于等于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充专题数据...', $tenant->name));

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $topic = Topic::factory()->create([
                'tenant_id' => $tenantId,
                'sort' => $i,
            ]);

            if ($productPerTopic > 0) {
                $products = Product::where('tenant_id', $tenantId)
                    ->inRandomOrder()
                    ->limit($productPerTopic)
                    ->pluck('id');

                if ($products->isNotEmpty()) {
                    $topic->products()->attach($products);
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("专题数据填充完成！共创建 {$count} 个专题");
    }
}
