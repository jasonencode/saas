<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Content\CategoryType;
use App\Models\Content\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\text;

#[Signature('seed:contents')]
class ContentSeeder extends Command
{
    public function handle(): void
    {
        $categoryCount = (int) text(
            label: '每个租户创建内容分类数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value >= 0 ? null : '请输入大于等于 0 的数字',
        );
        $count = (int) text(
            label: '每个租户创建内容数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenants = Tenant::all();
        $progressBar = $this->output->createProgressBar($tenants->count());
        $progressBar->start();

        foreach ($tenants as $tenant) {
            DB::transaction(function () use ($tenant, $categoryCount, $count) {
                $categories = $this->createCategories($tenant, $categoryCount);
                $this->createContents($tenant, $count, $categories);
            });
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('内容数据填充完成！');
    }

    /**
     * 为租户创建内容分类（若无分类则内容无法关联）
     *
     * @return Collection<int, Category>
     */
    protected function createCategories(Tenant $tenant, int $count): Collection
    {
        $categories = collect();
        for ($i = 0; $i < $count; $i++) {
            $categories->push(ContentCategory::create([
                'tenant_id' => $tenant->id,
                'name' => fake('zh_CN')->word().'内容分类',
                'type' => CategoryType::Content,
                'status' => true,
                'sort' => $i,
            ]));
        }

        return $categories;
    }

    /**
     * 为租户创建内容并关联该租户的分类
     *
     * @param  Collection<int, Category>  $categories
     */
    protected function createContents(Tenant $tenant, int $count, Collection $categories): void
    {
        // 本次新建分类为 0 时，回退到该租户已有的内容分类
        $attachable = $categories->isNotEmpty()
            ? $categories
            : ContentCategory::where('tenant_id', $tenant->id)
                ->where('type', CategoryType::Content)
                ->get();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage(sprintf('租户 #%d 内容填充', $tenant->id));
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $content = Content::create([
                'tenant_id' => $tenant->id,
                'title' => fake('zh_CN')->sentence(),
                'content' => fake('zh_CN')->paragraph(),
                'status' => true,
            ]);

            if ($attachable->isNotEmpty()) {
                $content->categories()->attach($attachable->random()->id);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }
}
