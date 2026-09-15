<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Enums\Mall\FulfillmentType;
use App\Models\Mall\Brand;
use App\Models\Mall\Delivery;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:products')]
class ProductSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '商品填充';
    }

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );
        $brandCount = (int) text(
            label: '每个租户创建品牌数量',
            default: '0',
            validate: fn ($value) => is_numeric($value) && $value >= 0 ? null : '请输入大于等于 0 的数字',
        );
        $categoryCount = (int) text(
            label: '每个租户创建分类数量',
            default: '0',
            validate: fn ($value) => is_numeric($value) && $value >= 0 ? null : '请输入大于等于 0 的数字',
        );
        $productCount = (int) text(
            label: '每个租户创建商品数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充数据...', $tenant->name));

        DB::transaction(function () use ($tenant, $brandCount, $categoryCount, $productCount) {
            $brands = $this->getBrands($tenant, $brandCount);
            $categories = $this->getCategories($tenant, $categoryCount);
            $this->createProducts($tenant, $productCount, $brands, $categories);
        });

        $this->newLine();
        $this->info('商品数据填充完成！');
    }

    protected function getBrands(Tenant $tenant, int $count): Collection
    {
        $existing = Brand::where('tenant_id', $tenant->id)->get();

        if ($count === 0) {
            return $existing;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage('创建品牌');
        $progressBar->start();

        $new = collect();
        for ($i = 0; $i < $count; $i++) {
            $new->push(Brand::factory()->create([
                'tenant_id' => $tenant->id,
                'sort' => $i,
            ]));
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $existing->merge($new);
    }

    protected function getCategories(Tenant $tenant, int $count): Collection
    {
        $existing = ProductCategory::where('tenant_id', $tenant->id)->get();

        if ($count === 0) {
            return $existing;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage('创建分类');
        $progressBar->start();

        $new = collect();
        for ($i = 0; $i < $count; $i++) {
            $parent = ProductCategory::factory()->create([
                'tenant_id' => $tenant->id,
                'sort' => $i,
            ]);

            for ($j = 0; $j < 3; $j++) {
                $new->push(ProductCategory::factory()->childOf($parent)->create([
                    'tenant_id' => $tenant->id,
                    'sort' => $j,
                ]));
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $existing->merge($new);
    }

    protected function createProducts(Tenant $tenant, int $count, Collection $brands, Collection $categories): void
    {
        if ($brands->isEmpty() || $categories->isEmpty()) {
            $this->warn("租户 [$tenant->name] 跳过商品创建：缺少品牌或分类");

            return;
        }

        $deliveryId = Delivery::where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->value('id');

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage(sprintf('租户 #%d 商品填充', $tenant->id));
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $product = Product::factory()->create([
                'tenant_id' => $tenant->id,
                'brand_id' => $brands->random()->id,
                'category_id' => $categories->random()->id,
                'delivery_id' => $deliveryId,
                'sort' => $i,
                'views' => random_int(100, 10000),
                'fulfillment_type' => [
                    FulfillmentType::Mail->value,
                    FulfillmentType::Pickup->value,
                ],
            ]);

            $this->createSkus($product);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function createSkus(Product $product): void
    {
        $colors = ['红色', '蓝色', '黑色', '白色'];
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $selectedColors = collect($colors)->random(random_int(1, 2))->toArray();
        $selectedSizes = collect($sizes)->random(random_int(1, 2))->toArray();

        foreach ($selectedColors as $color) {
            foreach ($selectedSizes as $size) {
                Sku::factory()->create([
                    'product_id' => $product->getKey(),
                    'name' => $color.'/'.$size,
                ]);
            }
        }
    }
}
