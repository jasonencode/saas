<?php

namespace App\Filament\Forms\Components;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;

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
            ->directory(function() {
                $tenant = Filament::getTenant();

                if ($tenant) {
                    return $tenant->getKey().'/'.date('Y/m/d');
                }

                return '0/'.date('Y/m/d');
            })
            ->moveFiles()
            ->orientImagesFromExif(false)
            ->fetchFileInformation(false);
    }
}
