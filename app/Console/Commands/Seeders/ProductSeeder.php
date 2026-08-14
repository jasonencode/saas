<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Content\CategoryType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Brand;
use App\Models\Mall\Delivery;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:products')]
class ProductSeeder extends Command
{
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
            $new->push(Brand::create([
                'tenant_id' => $tenant->id,
                'name' => fake('zh_CN')->company().'品牌',
                'status' => true,
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
        $existing = ProductCategory::where('tenant_id', $tenant->id)->where('type', CategoryType::Product)->get();

        if ($count === 0) {
            return $existing;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage('创建分类');
        $progressBar->start();

        $new = collect();
        for ($i = 0; $i < $count; $i++) {
            $parent = ProductCategory::create([
                'tenant_id' => $tenant->id,
                'name' => fake('zh_CN')->word().'大类',
                'type' => CategoryType::Product,
                'status' => true,
                'sort' => $i,
            ]);

            for ($j = 0; $j < 3; $j++) {
                $new->push(ProductCategory::create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $parent->id,
                    'name' => fake('zh_CN')->word().'子类',
                    'type' => CategoryType::Product,
                    'status' => true,
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

        // 租户默认运费模板，绑定到商品上以便下单时计算运费
        $deliveryId = Delivery::where('tenant_id', $tenant->id)
            ->where('is_default', true)
            ->value('id');

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage(sprintf('租户 #%d 商品填充', $tenant->id));
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $product = Product::create([
                'tenant_id' => $tenant->id,
                'brand_id' => $brands->random()->id,
                'name' => fake('zh_CN')->words(3, true).'商品',
                'description' => fake('zh_CN')->sentence(),
                'status' => ProductStatus::Up,
                'sort' => $i,
                'views' => random_int(100, 10000),
                'category_id' => $categories->random()->id,
                'delivery_id' => $deliveryId,
                'fulfillment_type' => [
                    FulfillmentType::Mail,
                    FulfillmentType::Pickup,
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
                $product->skus()->create([
                    'name' => $color.'/'.$size,
                    'origin_price' => random_int(100, 200),
                    'price' => random_int(50, 99),
                    'stock' => random_int(10, 1000),
                    'sale' => random_int(0, 100),
                    'code' => fake()->ean13(),
                    'weight' => random_int(1, 5) + (random_int(0, 9) / 10),
                    'volume' => random_int(1, 10) / 100,
                ]);
            }
        }
    }
}
