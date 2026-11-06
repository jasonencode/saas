<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Campaign\SeckillActivityStatus;
use App\Enums\Campaign\SeckillSessionStatus;
use App\Enums\Mall\ProductStatus;
use App\Models\Campaign\SeckillActivity;
use App\Models\Campaign\SeckillSession;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Collection;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:seckill {--tenant= : 租户ID} {--activities= : 活动数量} {--sessions= : 每个活动的场次数量}')]
#[CommandLabel('秒杀填充')]
class SeckillSeeder extends BaseCommand
{
    public function handle(): void
    {
        $tenantId = $this->option('tenant') !== null
            ? (int) $this->option('tenant')
            : (int) select(
                label: '选择租户',
                options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
            );

        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            $this->error('租户不存在');

            return;
        }

        $activityCount = $this->option('activities') !== null
            ? max(1, (int) $this->option('activities'))
            : (int) text(
                label: '创建活动数量',
                default: '1',
                validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
            );

        $count = $this->option('sessions') !== null
            ? max(1, (int) $this->option('sessions'))
            : (int) text(
                label: '每个活动的场次数量',
                default: '5',
                validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
            );

        $skus = $this->availableSkus($tenantId);

        if ($skus->isEmpty()) {
            $this->error('该租户没有可用的上架 SKU（需要库存大于 0）');

            return;
        }

        $this->info(sprintf(
            '开始为租户 [%s] 创建 %d 个活动，每个活动 %d 个场次...',
            $tenant->name,
            $activityCount,
            $count,
        ));

        $total = $activityCount * $count;

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $counter = service(RedisSeckillStockCounter::class);

        // 已存在的进行中场次不重复创建；新场次从已有最晚场次之后顺延，避免同 SKU 时间重叠
        $hasRunning = SeckillSession::query()
            ->where('tenant_id', $tenantId)
            ->where('status', SeckillSessionStatus::Running->value)
            ->exists();

        $latestStart = SeckillSession::query()->where('tenant_id', $tenantId)->max('start_at');
        $dayCursor = $latestStart !== null
            ? Carbon::parse($latestStart)->startOfDay()
            : now()->startOfDay();

        $created = 0;
        $sessionIndex = 0;

        for ($activityIndex = 1; $activityIndex <= $activityCount; $activityIndex++) {
            $activity = SeckillActivity::create([
                'tenant_id' => $tenantId,
                'name' => $tenant->name.'·限时秒杀'.($activityCount > 1 ? " {$activityIndex}" : ''),
                'description' => '测试数据：由 seed:seckill 生成',
                'status' => SeckillActivityStatus::Published,
                'sort' => $activityIndex,
            ]);

            for ($i = 0; $i < $count; $i++) {
                $sku = $skus[$sessionIndex % $skus->count()];
                $sessionIndex++;

                if (!$hasRunning) {
                    $startAt = now()->subMinutes(30);
                    $status = SeckillSessionStatus::Running;
                    $hasRunning = true;
                } else {
                    $dayCursor = $dayCursor->copy()->addDay();
                    $startAt = $dayCursor->copy()->setTime(10, 0);
                    $status = SeckillSessionStatus::Scheduled;
                }

                $session = SeckillSession::create([
                    'tenant_id' => $tenantId,
                    'activity_id' => $activity->getKey(),
                    'name' => $startAt->format('m-d H:i').' 场',
                    'product_id' => $sku->product_id,
                    'sku_id' => $sku->getKey(),
                    'seckill_price' => $this->seckillPrice($sku),
                    'stock' => min(50, $sku->stock),
                    'sold' => 0,
                    'limit_per_user' => fake()->numberBetween(1, 3),
                    'start_at' => $startAt,
                    'end_at' => $startAt->copy()->addHours(2),
                    'status' => $status,
                ]);

                $counter->prepare($session);

                $created++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("秒杀数据填充完成！活动 {$activityCount} 个，场次 {$created} 个");
    }

    /**
     * 取该租户有库存的上架 SKU
     *
     * @return Collection<int, Sku>
     */
    private function availableSkus(int $tenantId): Collection
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ProductStatus::Up)
            ->whereHas('skus', fn ($query) => $query->where('stock', '>', 0)->where('price', '>', 0.01))
            ->with(['skus' => fn ($query) => $query->where('stock', '>', 0)->where('price', '>', 0.01)])
            ->get()
            ->flatMap(fn (Product $product) => $product->skus)
            ->values();
    }

    /**
     * 按原价 7 折生成秒杀价，并保证低于原价
     */
    private function seckillPrice(Sku $sku): string
    {
        $price = max(0.01, round((float) $sku->price * 0.7, 2));

        return number_format(min($price, (float) $sku->price - 0.01), 2, '.', '');
    }
}
