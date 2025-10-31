<?php

namespace App\Filament\Actions\Setting;

use App\Models\Module;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Nwidart\Modules\Facades\Module as ModuleFacade;

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
            ModuleFacade::enable($moduleName);
        }
    }

    private function installModulesFromZip(TemporaryUploadedFile $zip)
    {

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
