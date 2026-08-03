<?php

namespace App\Filament\Forms\Components;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * 自定义文件上传组件
 *
 * 提供按租户与日期分目录的文件上传、封面图、轮播图等快捷构造方法，
 * 内部基于 Filament FileUpload 组件并约定统一的存储命名与目录规则。
 */
class CustomUpload
{
    /**
     * 文件上传组件
     *
     * 默认按「租户 ID/Y/m/d」分目录存储，文件名使用哈希值保留原扩展名，
     * 不自动修正图片方向，不上传后立即抓取文件信息。
     *
     * @param  string  $field  表单字段名
     *
     * @return FileUpload 文件上传组件实例
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
     * 在通用上传组件基础上启用可下载、图片类型、图片编辑器（双模式），
     * 并约定 16:9、4:3、1:1 三种裁剪比例。
     *
     * @param  string  $field  表单字段名
     * @param  string  $label  字段展示标签
     *
     * @return FileUpload 封面图上传组件实例
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
     * 在通用上传组件基础上启用多图、可排序、可下载、图片类型、图片编辑器（双模式），
     * 并约定 16:9、4:3、1:1 三种裁剪比例。
     *
     * @param  string  $field  表单字段名
     * @param  string  $label  字段展示标签
     *
     * @return FileUpload 轮播图上传组件实例
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

    /**
     * 获取上传存储目录
     *
     * 按当前租户 ID 与日期（Y/m/d）拼装目录，租户未取到时以「0」兜底，
     * 用于将各租户的上传文件按租户与日期物理隔离。
     *
     * @return string 存储目录路径（如「1/2026/08/03」）
     */
    protected static function getDirectory(): string
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            return $tenant->getKey().'/'.date('Y/m/d');
        }

        return '0/'.date('Y/m/d');
    }
}
