<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Enums\Mall\RegionLevel;
use App\Models\Mall\Delivery;
use App\Models\Mall\DeliveryRule;
use App\Models\Mall\Region;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

use function Laravel\Prompts\select;

#[Signature('seed:delivery-rules')]
#[CommandLabel('配送规则填充')]
class DeliveryRuleSeeder extends BaseCommand
{
    /**
     * 偏远地区省份名称清单
     *
     * @var array<int, string>
     */
    protected array $remoteProvinces = [
        '新疆',
        '西藏',
        '青海',
        '内蒙古',
        '宁夏',
        '甘肃',
    ];

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );

        $tenant = Tenant::find($tenantId);

        $this->info("租户 [{$tenant->name}] 开始创建运费模板...");

        $ruleCount = $this->createForTenant($tenant);

        $this->info("运费模板创建完毕，共生成 {$ruleCount} 条规则。");
    }

    /**
     * 为单个租户创建运费模板与差异化地区规则
     *
     * @return int 生成的规则数量
     */
    protected function createForTenant(Tenant $tenant): int
    {
        $delivery = $this->createDelivery($tenant);
        $this->line("模板 [{$delivery->name}] 创建成功 (ID: {$delivery->id})");

        $ruleCount = 0;

        $remoteIds = $this->remoteProvinceIds();

        if ($remoteIds->isEmpty()) {
            $this->warn("租户 [{$tenant->name}] 未找到偏远地区省份，跳过偏远地区规则创建。");

            return $ruleCount;
        }

        foreach ($remoteIds as $provinceId) {
            $region = Region::find($provinceId);

            if (!$region instanceof Region) {
                continue;
            }

            DeliveryRule::factory()->forProvince($provinceId)->create([
                'delivery_id' => $delivery->id,
                'first_fee' => 18,
                'additional_fee' => 8,
                'free_shipping_threshold' => 199,
                'region_code' => (string) $provinceId,
                'region_name' => $region->name,
                'sort' => 200,
            ]);
            $ruleCount++;
        }

        return $ruleCount;
    }

    /**
     * 创建运费模板
     */
    protected function createDelivery(Tenant $tenant): Delivery
    {
        return Delivery::factory()->weight()->asDefault()->create([
            'tenant_id' => $tenant->id,
            'name' => '默认运费模板',
            'first_fee' => 10,
            'additional_fee' => 5,
            'free_shipping_threshold' => 99,
        ]);
    }

    /**
     * 查询偏远地区省份 ID 集合
     *
     * @return Collection<int, int>
     */
    protected function remoteProvinceIds(): Collection
    {
        return Region::where('level', RegionLevel::Province)
            ->where(function (Builder $query): void {
                foreach ($this->remoteProvinces as $name) {
                    $query->orWhere('name', 'like', '%'.$name.'%');
                }
            })
            ->pluck('id');
    }
}
