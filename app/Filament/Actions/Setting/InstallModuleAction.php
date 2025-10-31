<?php

namespace App\Filament\Actions\Setting;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Throwable;
use ZipArchive;

class InstallModuleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'installModule';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label('安装模块');
        $this->icon(Heroicon::ArrowUpTray);
        $this->modalWidth(Width::Large);
        $this->schema($this->getUploadSchema());
        $this->action(fn(array $data) => $this->handleInstall($data));
    }

    private function handleInstall(array $data): void
    {
        if ($data['source'] === 'zip' && $data['zip']) {
            $this->installModulesFromZip($data['zip']);
        }
        if ($data['source'] === 'path' && $data['path']) {
            $this->installFromPath($data['path']);
        }
    }

    private function installFromPath(string $path): void
    {
        $moduleName = basename($path);

        if ($this->isValidModule($moduleName)) {
            ModuleFacade::scan();
            ModuleFacade::find($moduleName)->enable();
        }
    }

    private function installModulesFromZip(TemporaryUploadedFile $zip): void
    {
        // 1) 打开压缩包
        $zipPath = $zip->getRealPath();
        $zipArchive = new ZipArchive();
        $opened = $zipArchive->open($zipPath);
        if ($opened !== true) {
            $this->failureNotificationTitle('压缩包无法打开');
            $this->failure();

            return;
        }

        // 2) 解压到临时目录
        $extractBase = storage_path('app/tmp/modules_'.time().'_'.uniqid());
        File::ensureDirectoryExists($extractBase);
        $zipArchive->extractTo($extractBase);
        $zipArchive->close();

        // 3) 识别模块根目录（优先单一顶级目录，其次根）
        $topDirs = collect(File::directories($extractBase));
        $moduleRoot = $topDirs->count() === 1 ? $topDirs->first() : $extractBase;

        // 4) 解析模块名称（module.json -> name；否则目录名）
        $moduleName = null;
        $moduleJsonPath = $moduleRoot.DIRECTORY_SEPARATOR.'module.json';
        if (File::exists($moduleJsonPath)) {
            try {
                $meta = json_decode(File::get($moduleJsonPath), true);
                if (is_array($meta)) {
                    $moduleName = $meta['name'] ?? $meta['alias'] ?? null;
                }
            } catch (Throwable) {
                // ignore json parse errors
            }
        }
        if (!$moduleName) {
            $moduleName = basename($moduleRoot);
        }

        // 5) 目标路径与存在性校验
        $modulesBase = base_path('modules');
        File::ensureDirectoryExists($modulesBase);
        $dest = $modulesBase.DIRECTORY_SEPARATOR.$moduleName;
        if (File::exists($dest)) {
            File::deleteDirectory($extractBase);
            $this->failureNotificationTitle("模块 $moduleName 已存在");
            $this->failure();

            return;
        }

        // 6) 复制文件到 modules/<name>
        File::copyDirectory($moduleRoot, $dest);

        // 7) 清理临时目录
        File::deleteDirectory($extractBase);

        // 8) 安装后校验结构
        if (!$this->isValidModule($moduleName)) {
            File::deleteDirectory($dest);
            $this->failureNotificationTitle('模块结构无效或缺少必要文件');
            $this->failure();

            return;
        }

        // 9) 扫描并尝试启用模块
        try {
            ModuleFacade::scan();
            ModuleFacade::find($moduleName)->enable();
        } catch (Throwable) {
            // 即使启用失败，也提示安装成功，并在列表中手动启用
        }

        // 10) 通知并刷新表格
        $this->successNotificationTitle("模块 $moduleName 安装成功");
        $this->success();
    }

    private function isValidModule(string $folder): bool
    {
        $base = base_path("modules/$folder");

        if (!File::isDirectory($base)) {
            return false;
        }

        if (File::exists("$base/module.json") || File::exists("$base/composer.json")) {
            return true;
        }

        $phpFiles = collect(File::allFiles($base))
            ->filter(fn($file) => $file->getExtension() === 'php');

        return $phpFiles->isNotEmpty();
    }

    private function getUploadSchema(): array
    {
        return [
            Forms\Components\Radio::make('source')
                ->label('模块来源')
                ->options([
                    'zip' => '压缩文件',
                    'path' => '本地路径',
                ])
                ->inline()
                ->default('path')
                ->columnSpanFull()
                ->live()
                ->afterStateUpdated(function($state, callable $set) {
                    if ($state === 'zip') {
                        $set('path', null);
                    } elseif ($state === 'path') {
                        $set('zip', null);
                    }
                })
                ->required()
                ->reactive(),
            Forms\Components\FileUpload::make('zip')
                ->label('上传文件')
                ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])
                ->visible(fn(Get $get) => $get('source') === 'zip')
                ->required()
                ->storeFiles(false),
            Forms\Components\TextInput::make('path')
                ->label('本地路径')
                ->placeholder('/modules/Mall')
                ->default('/modules/Mall')
                ->visible(fn(Get $get) => $get('source') === 'path')
                ->required(),
        ];
    }
}
