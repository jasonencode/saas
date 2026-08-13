<?php

namespace App\Console\Commands\Maintenance;

use App\Models\BlockChain;
use App\Models\Campaign;
use App\Models\Content;
use App\Models\Mall;
use App\Models\System;
use App\Models\User;
use DOMAttr;
use DOMDocument;
use DOMXPath;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('maintenance:clean-files
{--disk=public : 要清理的存储磁盘，默认 public}
{--path= : 仅扫描该目录下的文件，默认扫描整个磁盘}
{--dry-run : 仅统计并列出未使用文件，不执行删除}
{--force : 跳过删除确认提示}')]
#[Description('清理未被数据库引用的文件')]
class CleanUnusedFilesCommand extends Command
{
    /**
     * 需要参与匹配的模型文件字段配置
     *
     * key 为模型类名，value 为字段定义列表：
     * - field: 字段名
     * - type: 字段存储类型
     *   - string: 单个相对路径（如 cover、avatar）
     *   - array: JSON 数组的多个相对路径（如 pictures、materials）
     *   - rich_text: HTML 富文本（RichEditor 等产出），提取其中的资源路径
     *
     * @var array<class-string<Model>, array<int, array{field: string, type: 'string'|'array'|'rich_text'}>>
     */
    protected array $sources = [
        Mall\Banner::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\Brand::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\Express::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\Product::class => [
            ['field' => 'cover', 'type' => 'string'],
            ['field' => 'pictures', 'type' => 'array'],
            ['field' => 'materials', 'type' => 'array'],
        ],
        Mall\Sku::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\ProductCategory::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\Supplier::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\StoreConfigure::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Mall\StoreApply::class => [
            ['field' => 'front', 'type' => 'string'],
            ['field' => 'back', 'type' => 'string'],
            ['field' => 'license', 'type' => 'string'],
        ],
        Campaign\Lottery::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Campaign\LotteryPrize::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Content\Content::class => [
            ['field' => 'cover', 'type' => 'string'],
            ['field' => 'content', 'type' => 'rich_text'],
        ],
        Content\ContentCategory::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        Content\SinglePage::class => [
            ['field' => 'cover', 'type' => 'string'],
            ['field' => 'content', 'type' => 'rich_text'],
        ],
        Content\Comment::class => [
            ['field' => 'pictures', 'type' => 'array'],
        ],
        User\Identity::class => [
            ['field' => 'cover', 'type' => 'string'],
        ],
        User\UserProfile::class => [
            ['field' => 'avatar', 'type' => 'string'],
        ],
        User\UserRealname::class => [
            ['field' => 'id_card_front', 'type' => 'string'],
            ['field' => 'id_card_back', 'type' => 'string'],
            ['field' => 'business_license', 'type' => 'string'],
        ],
        System\Administrator::class => [
            ['field' => 'avatar', 'type' => 'string'],
        ],
        System\Tenant::class => [
            ['field' => 'avatar', 'type' => 'string'],
        ],
        BlockChain\ContractRepository::class => [
            ['field' => 'source_path', 'type' => 'string'],
        ],
        Import::class => [
            ['field' => 'file_path', 'type' => 'string'],
        ],
    ];

    /**
     * 数据库已引用的文件路径集合
     *
     * @var array<string, true>
     */
    protected array $referencedPaths = [];

    /**
     * 未使用文件列表
     *
     * @var array<int, array{path: string, size: int}>
     */
    protected array $unusedFiles = [];

    public function handle(): int
    {
        $disk = Storage::disk($this->option('disk'));

        if (!$this->isDiskUsable($disk)) {
            $this->error(sprintf('磁盘 [%s] 不可用，请检查配置。', $this->option('disk')));

            return self::FAILURE;
        }

        $this->line(sprintf('磁盘 [%s] 开始收集数据库引用文件...', $this->option('disk')));
        $this->collectReferencedPaths();

        $this->line(sprintf('磁盘 [%s] 开始扫描文件...', $this->option('disk')));
        $this->scanFiles($disk);

        return $this->handleUnusedFiles($disk);
    }

    /**
     * 检查磁盘是否可用
     *
     * @param  Filesystem  $disk  文件系统磁盘
     */
    protected function isDiskUsable(Filesystem $disk): bool
    {
        try {
            $disk->exists('/');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 从数据库各模型收集所有被引用的文件路径
     */
    protected function collectReferencedPaths(): void
    {
        $progressBar = $this->output->createProgressBar(count($this->sources));
        $progressBar->start();

        foreach ($this->sources as $model => $fields) {
            $this->collectModelPaths($model, $fields);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info(sprintf('数据库已引用文件 %d 个。', count($this->referencedPaths)));
    }

    /**
     * 收集单个模型的文件路径
     *
     * @param  class-string<Model>  $model  模型类名
     * @param  array<int, array{field: string, type: 'string'|'array'|'rich_text'}>  $fields  字段定义列表
     */
    protected function collectModelPaths(string $model, array $fields): void
    {
        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        $fieldNames = array_column($fields, 'field');
        $keyName = $query->getModel()->getKeyName();

        $query->select([...$fieldNames, $keyName])
            ->chunkById(500, function (EloquentCollection $records) use ($fields): void {
                /** @var Model $record */
                foreach ($records as $record) {
                    foreach ($fields as $field) {
                        $value = $record->{$field['field']};

                        match ($field['type']) {
                            'rich_text' => $this->collectRichTextPaths($value),
                            default => $this->collectFieldPaths($value),
                        };
                    }
                }
            }, $keyName, $keyName);
    }

    /**
     * 从富文本 HTML 内容中提取资源路径
     *
     * 使用 DOMDocument 解析 HTML，提取 img/source/video/audio/iframe 等标签的
     * src/poster 属性及 Markdown 图片语法中的资源路径。
     *
     * @param  mixed  $value  富文本内容
     */
    protected function collectRichTextPaths(mixed $value): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        foreach ($this->extractHtmlResourcePaths($value) as $path) {
            $this->addReferencedPath($path);
        }

        // Markdown 图片语法 ![alt](path)
        preg_match_all('/!\[[^]]*]\(([^)\s]+)\)/', $value, $mdMatches);
        foreach ($mdMatches[1] ?? [] as $path) {
            $this->addReferencedPath(html_entity_decode($path));
        }
    }

    /**
     * 使用 DOMDocument 从 HTML 中提取资源路径
     *
     * @param  string  $html  HTML 内容
     *
     * @return array<int, string> 提取到的资源路径列表
     */
    protected function extractHtmlResourcePaths(string $html): array
    {
        $paths = [];

        $dom = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $paths;
        }

        $xpath = new DOMXPath($dom);

        $expressions = [
            '//img/@src',
            '//source/@src',
            '//video/@poster',
            '//audio/@src',
            '//iframe/@src',
        ];

        foreach ($expressions as $expression) {
            foreach ($xpath->query($expression) as $attribute) {
                /** @var DOMAttr $attribute */
                $value = html_entity_decode(trim($attribute->value));

                if ($value !== '') {
                    $paths[] = $value;
                }
            }
        }

        return $paths;
    }

    /**
     * 解析单个字段值并收集文件路径
     */
    protected function collectFieldPaths(mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->collectFieldPaths($item);
            }

            return;
        }

        if (!is_string($value) || $value === '') {
            return;
        }

        $this->addReferencedPath($value);
    }

    /**
     * 标准化并记录被引用的文件路径
     *
     * 仅匹配磁盘相对路径；外部 URL、站点静态资源（/images/...）不参与匹配。
     */
    protected function addReferencedPath(string $path): void
    {
        $normalized = ltrim($path, '/');

        if ($this->looksExternal($normalized)) {
            return;
        }

        $this->referencedPaths[$normalized] = true;
    }

    /**
     * 判断是否为外部 URL 或站点静态资源
     */
    protected function looksExternal(string $path): bool
    {
        if ($path === '') {
            return true;
        }

        if (preg_match('#^(?:[a-z][a-z0-9+.-]*://|//)#i', $path)) {
            return true;
        }

        return str_starts_with($path, 'images/') || str_starts_with($path, 'storage/');
    }

    /**
     * 扫描时需要排除的目录
     *
     * livewire-tmp 为 Livewire 临时上传目录，不参与清理。
     *
     * @var array<int, string>
     */
    protected array $excludedDirectories = [
        'livewire-tmp',
    ];

    /**
     * 判断文件是否位于需要排除的目录中
     */
    protected function isExcludedDirectory(string $path): bool
    {
        return array_any($this->excludedDirectories, fn ($directory) => $path === $directory || str_starts_with($path, $directory.'/'));
    }

    /**
     * 判断是否为隐藏文件（以点开头的 dotfile）
     *
     * .gitignore、.gitattributes、.DS_Store 等隐藏文件不参与清理。
     */
    protected function isHiddenFile(string $path): bool
    {
        return array_any(explode('/', $path), fn ($segment) => $segment !== '' && str_starts_with($segment, '.'));
    }

    /**
     * 扫描磁盘文件，找出未使用的文件
     *
     * @param  Filesystem  $disk  文件系统磁盘
     */
    protected function scanFiles(Filesystem $disk): void
    {
        $path = (string) $this->option('path');

        try {
            $contents = $disk->allFiles($path);
        } catch (Throwable $e) {
            $this->error(sprintf('扫描目录 [%s] 失败：%s', $path ?: '/', $e->getMessage()));

            return;
        }

        $totalSize = 0;
        $progressBar = $this->output->createProgressBar(count($contents));
        $progressBar->start();

        foreach ($contents as $file) {
            if ($this->isExcludedDirectory($file) || $this->isHiddenFile($file)) {
                continue;
            }

            $normalized = ltrim($file, '/');

            if (!isset($this->referencedPaths[$normalized])) {
                $size = 0;
                try {
                    $size = $disk->size($file);
                } catch (Throwable) {
                    // 忽略无法获取大小的文件
                }
                $this->unusedFiles[] = [
                    'path' => $file,
                    'size' => $size,
                ];
                $totalSize += $size;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info(sprintf('磁盘扫描完成：共 %d 个文件，其中未使用 %d 个，预计释放空间 %s。', count($contents), count($this->unusedFiles), $this->humanBytes($totalSize)));
    }

    /**
     * 处理未使用文件（预览或删除）
     *
     * @param  Filesystem  $disk  文件系统磁盘
     */
    protected function handleUnusedFiles(Filesystem $disk): int
    {
        if (empty($this->unusedFiles)) {
            $this->info('没有需要清理的文件。');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('以下文件未被数据库引用：');

        $totalSize = 0;
        foreach ($this->unusedFiles as $index => $file) {
            $this->line(sprintf('  %d. %s (%s)', $index + 1, $file['path'], $this->humanBytes($file['size'])));
            $totalSize += $file['size'];
        }

        $this->line(sprintf('共 %d 个文件，合计 %s。', count($this->unusedFiles), $this->humanBytes($totalSize)));

        if ($this->option('dry-run')) {
            $this->info('已执行 dry-run，未删除任何文件。');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('确定要删除这些未使用的文件吗？')) {
            $this->info('操作已取消。');

            return self::SUCCESS;
        }

        $this->deleteUnusedFiles($disk);

        return self::SUCCESS;
    }

    /**
     * 删除未使用文件
     *
     * @param  Filesystem  $disk  文件系统磁盘
     */
    protected function deleteUnusedFiles(Filesystem $disk): void
    {
        $progressBar = $this->output->createProgressBar(count($this->unusedFiles));
        $progressBar->start();

        $deleted = 0;
        foreach ($this->unusedFiles as $file) {
            try {
                $disk->delete($file['path']);
                $deleted++;
            } catch (Throwable $e) {
                $this->newLine();
                $this->error(sprintf('删除失败 [%s]：%s', $file['path'], $e->getMessage()));
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info(sprintf('清理完成，共删除 %d / %d 个文件。', $deleted, count($this->unusedFiles)));
    }

    /**
     * 格式化字节数为可读大小
     */
    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return sprintf('%s %s', rtrim(rtrim(number_format($size, 2), '0'), '.'), $units[$unit]);
    }
}
