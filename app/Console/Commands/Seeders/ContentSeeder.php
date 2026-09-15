<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use App\Models\System\Tenant;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:contents')]
class ContentSeeder extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '内容填充';
    }

    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );
        $categoryCount = (int) text(
            label: '创建内容分类数量',
            default: '3',
            validate: fn ($value) => is_numeric($value) && $value >= 0 ? null : '请输入大于等于 0 的数字',
        );
        $count = (int) text(
            label: '创建内容数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $tenant = Tenant::find($tenantId);

        $this->info(sprintf('开始为租户 [%s] 填充数据...', $tenant->name));

        DB::transaction(function () use ($tenant, $categoryCount, $count) {
            $categories = $this->createCategories($tenant, $categoryCount);
            $this->createContents($tenant, $count, $categories);
        });

        $this->newLine();
        $this->info('内容数据填充完成！');
    }

    /**
     * 为租户创建内容分类（若无分类则内容无法关联）
     *
     * @return Collection<int, ContentCategory>
     */
    protected function createCategories(Tenant $tenant, int $count): Collection
    {
        if ($count === 0) {
            return collect();
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage('创建分类');
        $progressBar->start();

        $categories = collect();
        for ($i = 0; $i < $count; $i++) {
            $categories->push(ContentCategory::factory()->create([
                'tenant_id' => $tenant->id,
                'sort' => $i,
            ]));
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $categories;
    }

    /**
     * 为租户创建内容并关联该租户的分类
     *
     * @param  Collection<int, ContentCategory>  $categories
     */
    protected function createContents(Tenant $tenant, int $count, Collection $categories): void
    {
        $attachable = $categories->isNotEmpty()
            ? $categories
            : ContentCategory::where('tenant_id', $tenant->id)->get();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setMessage(sprintf('租户 #%d 内容填充', $tenant->id));
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            Content::factory()->create([
                'tenant_id' => $tenant->id,
                'category_id' => $attachable->isNotEmpty() ? $attachable->random()->id : null,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }
}
