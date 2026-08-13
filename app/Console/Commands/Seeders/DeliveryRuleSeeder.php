<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Mall\DeliveryType;
use App\Enums\Mall\RegionLevel;
use App\Models\Mall\Delivery;
use App\Models\Mall\DeliveryRule;
use App\Models\Mall\Region;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

use function Laravel\Prompts\select;

#[Signature('seed:delivery-rules')]
class DeliveryRuleSeeder extends Command
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

        // 偏远地区省份规则（未命中的地区走模板默认值）
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

            $this->createRule($delivery, [
                'first' => 1,
                'first_fee' => 18,
                'additional' => 1,
                'additional_fee' => 8,
                'free_shipping_threshold' => 199,
            ], [
                'province_id' => $provinceId,
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
        return Delivery::create([
            'tenant_id' => $tenant->id,
            'name' => '默认运费模板',
            'type' => DeliveryType::Weight,
            'first' => 1,
            'first_fee' => 10,
            'additional' => 1,
            'additional_fee' => 5,
            'free_shipping_threshold' => 99,
            'is_default' => true,
            'status' => true,
        ]);
    }

    /**
     * 创建单条运费规则
     *
     * @param  array<string, float>  $fee
     * @param  array<string, mixed>  $extra
     */
    protected function createRule(Delivery $delivery, array $fee, array $extra): DeliveryRule
    {
        return DeliveryRule::create(array_merge([
            'delivery_id' => $delivery->id,
            'first' => $fee['first'],
            'first_fee' => $fee['first_fee'],
            'additional' => $fee['additional'],
            'additional_fee' => $fee['additional_fee'],
            'free_shipping_threshold' => $fee['free_shipping_threshold'],
            'status' => true,
        ], $extra));
    }

    /**
     * 查询偏远地区省份 ID 集合
     *
     * 使用 like 模糊匹配，兼容「新疆」与「新疆维吾尔自治区」等不同命名形式。
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
