<?php

namespace App\Filament\Forms\Components;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CustomUpload
{
    /**
     * 文件上传组件
     *
     * @param  string  $field
     * @return FileUpload
     */
    public static function make(string $field = 'cover'): FileUpload
    {
        return FileUpload::make($field)
            ->directory(self::getDirectory())
            ->moveFiles()
            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file) {
                $name = File::hash($file->path());
                $extension = strtolower($file->getClientOriginalExtension());

                return sprintf('%s.%s', $name, $extension);
            })
            ->orientImagesFromExif(false)
            ->fetchFileInformation(false);
    }

    /**
     * 封面图组件
     *
     * @param  string  $field
     * @param  string  $label
     * @return FileUpload
     */
    public static function cover(string $field = 'cover', string $label = '封面图'): FileUpload
    {
        return self::make($field)
            ->label($label)
            ->downloadable()
            ->image()
            ->imageEditor()
            ->imageEditorMode(2)
            ->imageEditorAspectRatioOptions([
                '16:9',
                '4:3',
                '1:1',
            ]);
    }

    /**
     * 轮播图组件
     *
     * @param  string  $field
     * @param  string  $label
     * @return FileUpload
     */
    public static function pictures(string $field = 'pictures', string $label = '轮播图'): FileUpload
    {
        return self::make($field)
            ->label($label)
            ->multiple()
            ->reorderable()
            ->downloadable()
            ->image()
            ->imageEditor()
            ->imageEditorMode(2)
            ->imageEditorAspectRatioOptions([
                '16:9',
                '4:3',
                '1:1',
            ]);
    }

    protected static function getDirectory(): string
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            return $tenant->getKey().'/'.date('Y/m/d');
        }

        return '0/'.date('Y/m/d');
    }
}
