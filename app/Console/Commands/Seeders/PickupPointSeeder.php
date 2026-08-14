<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Mall\RegionLevel;
use App\Models\Mall\PickupPoint;
use App\Models\Mall\Region;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:pickup-points')]
class PickupPointSeeder extends Command
{
    /**
     * 自提点名称前缀池
     */
    protected const array NAME_POOL = ['中心自提点', '城南自提点', '城北自提点', '东区自提点', '旗舰自提点', '社区自提点'];

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );
        $count = (int) text(
            label: '要生成的自提点数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            PickupPoint::create($this->buildData($tenantId, $i));
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('自提点数据填充完成！');
    }

    /**
     * 构造自提点数据
     *
     * @param  int  $tenantId  租户 ID
     * @param  int  $index  序号（用于名称去重）
     *
     * @return array<string, mixed>
     */
    protected function buildData(int $tenantId, int $index): array
    {
        $province = Region::where('level', RegionLevel::Province)->inRandomOrder()->first();
        $city = Region::where('level', RegionLevel::City)->inRandomOrder()->first();
        $district = Region::where('level', RegionLevel::District)->inRandomOrder()->first();

        return [
            'tenant_id' => $tenantId,
            'name' => self::NAME_POOL[$index % count(self::NAME_POOL)],
            'contact' => fake('zh_CN')->name(),
            'phone' => fake('zh_CN')->phoneNumber(),
            'province_id' => $province?->id,
            'city_id' => $city?->id,
            'district_id' => $district?->id,
            'address' => fake('zh_CN')->streetAddress(),
            'remark' => fake('zh_CN')->sentence(),
            'status' => true,
            'sort' => 0,
        ];
    }
}
