<?php

namespace App\Filament\Forms\Components;

use App\Models\Attachment;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CustomUpload
{
    /**
     * 创建文件上传组件，定义了上传目录和文件名称
     *
     * @param  string  $field
     * @return FileUpload
     */
    public static function make(string $field): FileUpload
    {
        return FileUpload::make($field)
            ->directory(date('Y/m/d'))
            ->preserveFilenames()
            ->moveFiles()
            ->orientImagesFromExif(false)
            ->fetchFileInformation(false)
            ->getUploadedFileNameForStorageUsing(function(TemporaryUploadedFile $file) {
                $hash = File::hash($file->path());

                Attachment::create([
                    'name' => $file->getClientOriginalName(),
                    'hash' => $hash,
                    'extension' => $file->getClientOriginalExtension(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'disk' => config('filesystems.default'),
                    'path' => date('Y/m/d/').$hash.'.'.$file->extension(),
                ]);

                return $hash.'.'.$file->extension();
            });
    }
}
